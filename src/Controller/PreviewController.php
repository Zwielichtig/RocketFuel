<?php

namespace App\Controller;

use App\Entity\Logic;
use App\Entity\Script;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

class PreviewController extends BaseController
{
    #[Route('/preview/{type}/{id}', name: 'preview')]
    public function viewPreview(string $type, int $id, EntityManagerInterface $entityManager): Response
    {
        // Validate type
        if (!in_array($type, ['logic', 'script'])) {
            throw $this->createNotFoundException('Invalid type specified');
        }

        // Get the appropriate entity
        $class = $type === 'logic' ? Logic::class : Script::class;
        $entity = $entityManager->getRepository($class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        // Check if the entity is public or if the user is the creator
        $isPublic = $entity->isPublished();
        $isCreator = $this->session->get('user') &&
                    $entity->getCreator()->getId() === $this->session->get('user')->getId();

        if (!$isPublic && !$isCreator) {
            throw $this->createAccessDeniedException('You do not have permission to view this content');
        }

        // Prepare the preview data
        $previewData = [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'description' => $entity->getDescription(),
            'category' => $entity->getCategory()->getName(),
            'creator' => $entity->getCreator()->getUsername(),
            'packageManager' => $entity->getPackageManager() ? $entity->getPackageManager()->getName() : null,
            'type' => $type,
            'content' => '',
            'blocks' => []
        ];

        if ($type === 'script') {
            // For scripts, try to read from YAML file
            $yamlPath = $this->getParameter('kernel.project_dir') . '/storage/' . $entity->getId() . '.yaml';

            if (file_exists($yamlPath)) {
                try {
                    $yamlData = Yaml::parseFile($yamlPath);

                    if (isset($yamlData['blocks'])) {
                        // Sort blocks by position
                        usort($yamlData['blocks'], function($a, $b) {
                            return ($a['position'] ?? 0) - ($b['position'] ?? 0);
                        });

                        foreach ($yamlData['blocks'] as $block) {
                            $blockData = [
                                'name' => $block['name'],
                                'content' => $block['content'],
                                'isCustom' => $block['isCustom'],
                                'position' => $block['position'],
                                'packagemanager' => null,
                                'description' => 'Custom Block',
                                'category' => 'Custom',
                                'creator' => 'User'
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
                            }

                            $previewData['blocks'][] = $blockData;
                        }

                        // Combine all block contents for the full script
                        $previewData['content'] = implode("\n\n", array_map(function($block) {
                            return $block['content'];
                        }, $yamlData['blocks']));
                    } else {
                        $previewData['content'] = $yamlData['script'] ?? '';
                    }
                } catch (\Exception $e) {
                    error_log('Error parsing YAML file: ' . $e->getMessage());
                    $previewData['content'] = '';
                }
            }
        } else {
            // For logics, get content directly from entity
            $previewData['content'] = $entity->getContent();
        }

        return $this->render('/layouts/preview.html.twig', [
            'user' => $this->session->get('user'),
            'preview' => $previewData
        ]);
    }

    #[Route('/raw/{type}/{id}', name: 'raw_content')]
    public function rawContent(string $type, int $id, EntityManagerInterface $entityManager): Response
    {
        // Only scripts are supported for raw content
        if ($type !== 'script') {
            throw $this->createNotFoundException('Raw content is only available for scripts');
        }

        // Get the script entity
        $script = $entityManager->getRepository(Script::class)->find($id);

        if (!$script) {
            throw $this->createNotFoundException('Script not found');
        }

        // Check if the script is public or if the user is the creator
        $isPublic = $script->isPublished();
        $isCreator = $this->session->get('user') &&
                    $script->getCreator()->getId() === $this->session->get('user')->getId();

        if (!$isPublic && !$isCreator) {
            throw $this->createAccessDeniedException('You do not have permission to access this content');
        }

        // Get the script content from YAML
        $yamlPath = $this->getParameter('kernel.project_dir') . '/storage/' . $script->getId() . '.yaml';
        $content = '';

        if (file_exists($yamlPath)) {
            try {
                $yamlData = Yaml::parseFile($yamlPath);

                if (isset($yamlData['blocks'])) {
                    // Sort blocks by position
                    usort($yamlData['blocks'], function($a, $b) {
                        return ($a['position'] ?? 0) - ($b['position'] ?? 0);
                    });

                    // Combine all block contents
                    $content = implode("\n\n", array_map(function($block) {
                        return $block['content'];
                    }, $yamlData['blocks']));
                } else {
                    $content = $yamlData['script'] ?? '';
                }
            } catch (\Exception $e) {
                error_log('Error parsing YAML file: ' . $e->getMessage());
                $content = '';
            }
        }

        // Create response with appropriate headers
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $script->getName() . '.sh"');

        // Add cache control headers
        $response->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
        $response->headers->set('ETag', md5($content));

        // Add CORS headers to allow direct access
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET');

        return $response;
    }
}