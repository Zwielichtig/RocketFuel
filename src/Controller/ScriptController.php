<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Logic;
use App\Entity\PackageManager;
use App\Entity\Script;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

class ScriptController extends BaseController
{
    #[Route('/editor/{type}/{id}', name: 'editor')]
    public function viewEditor(string $type, int $id, EntityManagerInterface $entityManager): Response
    {
        $parent = $entityManager->getRepository(Category::class)->findOneBy(['name' => $type.'s']);
        $categories = $entityManager->getRepository(Category::class)->findBy(['parent_id' => $parent->getId()]);

        $this->session->set('editorType', $type);
        $this->session->set('entityId', $id);

        return $this->render('/layouts/editor.html.twig', [
            'user' => $this->session->get('user'),
            'packageManager' => $entityManager->getRepository(PackageManager::class)->findAll(),
            'type' => $type,
            'categories' => $categories,
        ]);
    }

    #[Route('/ajax/editor/save', name: 'editorSave')]
    public function saveEditorAjax(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        if (0 === $this->session->get('entityId')) {
            if ('logic' === $this->session->get('editorType')) {
                $entity = new Logic;
            } else {
                $entity = new Script;
            }

            $this->persistEntity($entityManager, $entity, $data);

            return new JsonResponse([
                'status' => 'success',
                'redirect' => $this->generateUrl('editor', [
                    'type' => $this->session->get('editorType'),
                    'id' => $entity->getId(),
                ]),
            ]);
        }

        if ('logic' === $this->session->get('editorType')) {
            $entity = $entityManager->getRepository(Logic::class)->find($this->session->get('entityId'));
        } else {
            $entity = $entityManager->getRepository(Script::class)->find($this->session->get('entityId'));
        }

        $this->persistEntity($entityManager, $entity, $data);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Editor content saved successfully.',
        ]);
    }

    #[Route('/ajax/editor/searchLogics', name: 'searchLogics', methods: ['POST'])]
    public function searchLogics(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $queryString = json_decode($request->getContent(), true)['query'] ?? '';

        preg_match_all('/(user|category|pm):(\S+)/', $queryString, $matches, PREG_SET_ORDER);

        $filters = [
            'creator_id' => null,
            'category_id' => null,
            'packagemanager_id' => null,
            'text' => trim(preg_replace('/(user|category|pm):\S+/', '', $queryString))
        ];

        foreach ($matches as $match) {
            if ($match[1] === 'user') {
                $user = $entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.username LIKE :username')
                    ->setParameter('username', '%' . $match[2] . '%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($user) {
                    $filters['creator_id'] = $user->getId();
                }
            }

            if ($match[1] === 'category') {
                $category = $entityManager->getRepository(Category::class)
                    ->createQueryBuilder('t')
                    ->where('t.name LIKE :category')
                    ->setParameter('category', '%' . $match[2] . '%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($category) {
                    $filters['category_id'] = $category->getId();
                }
            }

            if ($match[1] === 'pm') {
                $packagemanager = $entityManager->getRepository(PackageManager::class)
                    ->createQueryBuilder('p')
                    ->where('p.name LIKE :packagemanager')
                    ->setParameter('packagemanager', '%' . $match[2] . '%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($packagemanager) {
                    $filters['packagemanager_id'] = $packagemanager->getId();
                }
            }
        }

        $qb = $entityManager->getRepository(Logic::class)->createQueryBuilder('l');

        if ($filters['creator_id']) {
            $qb->andWhere('l.creator = :creator_id')
                ->setParameter('creator_id', $filters['creator_id']);
        }

        if ($filters['category_id']) {
            $qb->andWhere('l.category = :category_id')
                ->setParameter('category_id', $filters['category_id']);
        }

        if ($filters['packagemanager_id']) {
            $qb->andWhere('l.package_manager = :packagemanager_id')
                ->setParameter('packagemanager_id', $filters['packagemanager_id']);
        }

        if (!empty($filters['text'])) {
            $qb->andWhere('l.name LIKE :text')
                ->setParameter('text', '%' . $filters['text'] . '%');
        }

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('l.published', 1),
                $qb->expr()->eq('l.creator', ':session_user_id')
            )
        )->setParameter('session_user_id', $this->session->get('user'));

        $logics = $qb->setMaxResults(5)->getQuery()->getResult();

        // Format results
        $data = array_map(fn($logic) => [
            'id' => $logic->getId(),
            'name' => $logic->getname(),
            'creator' => $logic->getCreator()->getUsername(),
            'category' => $logic->getCategory()->getname(),
            'description' => $logic->getDescription(),
            'content' => $logic->getContent(),
            'packagemanager' => $logic->getPackageManager() ? $logic->getPackageManager()->getname() : null,
        ], $logics);

        return new JsonResponse($data);
    }

    #[Route('/ajax/editor/getData', name: 'getEditorData')]
    public function getEditorData(EntityManagerInterface $entityManager): JsonResponse
    {
        $class = $this->session->get('editorType') === 'logic' ? Logic::class : Script::class;
        $entity = $entityManager->getRepository($class)->find($this->session->get('entityId'));

        if ($entity->getCreator()->getId() !== $this->session->get('user')->getId()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'You do not have permission to access this entity.',
            ]);
        }

        // Base data that's common for both types
        $data = [
            'name' => $entity->getName(),
            'description' => $entity->getDescription(),
            'category' => $entity->getCategory()->getId(),
            'packageManager' => $entity->getPackageManager() ? $entity->getPackageManager()->getId() : null,
            'isPublic' => $entity->isPublished(),
        ];

        if ($this->session->get('editorType') === 'script') {
            // For scripts, try to read from YAML file
            $yamlPath = $this->getParameter('kernel.project_dir') . '/storage/' . $entity->getId() . '.yaml';

            if (file_exists($yamlPath)) {
                try {
                    $yamlData = Yaml::parseFile($yamlPath);

                    // Ensure we have the correct structure
                    if (isset($yamlData['blocks'])) {
                        $dependencies = [];
                        $scriptContent = '';

                        // Sort blocks by position
                        usort($yamlData['blocks'], function($a, $b) {
                            return ($a['position'] ?? 0) - ($b['position'] ?? 0);
                        });

                        foreach ($yamlData['blocks'] as $block) {
                            // Add block content to script
                            if (isset($block['content'])) {
                                $scriptContent .= $block['content'] . "\n\n";
                            }

                            // Create block data
                            $blockData = [
                                'id' => $block['id'],
                                'name' => $block['name'],
                                'content' => $block['content'],
                                'isCustom' => $block['isCustom'],
                                'position' => $block['position']
                            ];

                            if (!$block['isCustom'] && $block['id']) {
                                // For non-custom blocks, get additional data from database
                                $logic = $entityManager->getRepository(Logic::class)->find($block['id']);
                                if ($logic) {
                                    $blockData = array_merge($blockData, [
                                        'description' => $logic->getDescription(),
                                        'category' => $logic->getCategory()->getName(),
                                        'packagemanager' => $logic->getPackageManager() ? $logic->getPackageManager()->getName() : null,
                                        'creator' => $logic->getCreator()->getUsername()
                                    ]);
                                }
                            } else {
                                // For custom blocks, add default values
                                $blockData = array_merge($blockData, [
                                    'description' => 'Custom Block',
                                    'category' => 'Custom',
                                    'creator' => 'User'
                                ]);
                            }

                            $dependencies[] = $blockData;
                        }

                        $data['script'] = trim($scriptContent);
                        $data['dependencies'] = $dependencies;
                    } else {
                        // If no blocks structure, use the raw script content
                        $data['script'] = $yamlData['script'] ?? '';
                        $data['dependencies'] = $yamlData['dependencies'] ?? [];
                    }
                } catch (\Exception $e) {
                    error_log('Error parsing YAML file: ' . $e->getMessage());
                    $data['script'] = '';
                    $data['dependencies'] = [];
                }
            } else {
                // If no YAML exists yet, use empty defaults
                $data['script'] = '';
                $data['dependencies'] = [];
            }
        } else {
            // For logics, get content directly from entity
            $data['script'] = $entity->getContent();
            $data['dependencies'] = [];
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    private function persistEntity(EntityManagerInterface $entityManager, $entity, $data): void
    {
        if ($entity->getId()) {
            $existingDependencies = $entity->getDependencies()->toArray();
            $newLogicIds = $data['logicIds'] ?? [];

            foreach ($existingDependencies as $dependency) {
                $dependencyId = $dependency->getId();

                if (($key = array_search($dependencyId, $newLogicIds)) !== false) {
                    unset($newLogicIds[$key]);
                } else {
                    $entity->removeDependency($dependency);
                }
            }

            foreach ($newLogicIds as $logicId) {
                $logic = $entityManager->getRepository(Logic::class)->find($logicId);
                $entity->addDependency($logic);
            }

            $entity->setModified(new \DateTime);
        }

        if ($this->session->get('editorType') === 'logic') {
            $entity->setContent($data['script']);
        }

        $entity->setCategory($entityManager->getRepository(Category::class)->find($data['values']['category']));
        $entity->setCreator($entityManager->getRepository(User::class)->find($this->session->get('user')));
        $entity->setName($data['values']['name']);
        $entity->setDescription($data['values']['description']);
        $entity->setPackageManager($data['values']['packageManager'] == 'null' ? null : $entityManager->getRepository(PackageManager::class)->find($data['values']['packageManager']));
        $entity->setCrdate(new \DateTimeImmutable());
        $entity->setPublished($data['values']['isPublic'] ?? false);

        $entityManager->persist($entity);
        $entityManager->flush();

        if ($this->session->get('editorType') === 'script') {
            // For scripts, always save to YAML, overwriting if it exists
            $yamlPath = $this->getParameter('kernel.project_dir') . '/storage/' . $entity->getId() . '.yaml';

            // Prepare the data to save
            $yamlData = [
                'blocks' => $data['blocks'] ?? [],
                'values' => $data['values'] ?? []
            ];

            try {
                // Save to YAML file, overwriting if it exists
                $yamlContent = Yaml::dump($yamlData);
                file_put_contents($yamlPath, $yamlContent);

                // Update the entity's path
                $entity->setPath('/storage/' . $entity->getId() . '.yaml');
                $entityManager->persist($entity);
                $entityManager->flush();
            } catch (\Exception $e) {
                throw new \RuntimeException('Failed to save script to YAML: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    #[Route('/delete/{type}/{id}', name: 'delete_content', methods: ['DELETE'])]
    public function delete(string $type, int $id, Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException('Direct access not allowed');
        }

        $user = $this->session->get('user');
        if (!$user) {
            throw $this->createAccessDeniedException('User must be logged in');
        }

        // Determine entity class based on type
        $entityClass = $type === 'script' ? Script::class : Logic::class;
        $entity = $this->entityManager->getRepository($entityClass)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException(ucfirst($type) . ' not found');
        }

        // Check if user is the creator
        if ($entity->getCreator()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only delete your own ' . $type . 's');
        }

        // For scripts, delete the associated file if it exists
        if ($type === 'script' && $entity->getPath()) {
            $filePath = $this->getParameter('kernel.project_dir') . $entity->getPath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Remove from database
        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}