import '../styles/app.scss';
import { UserContent } from './modules/UserContent';

document.addEventListener('DOMContentLoaded', async () => {
    const modulesToLoad = getTSModules();

    if (modulesToLoad) {
      const moduleHandlers = modulesToLoad.map(module =>
        module.replace('ts-module-', '') + 'Handler'
      );

      moduleHandlers.forEach(handler => {
        import(`./modules/${handler}`)
          .then((module) => {
            const handlerClass = module[handler];
            if (handlerClass && typeof handlerClass.getInstance === 'function') {
              handlerClass.getInstance().init();
            } else {
              console.warn(`Handler class "${handler}" not found in module.`);
            }
          })
          .catch((err) => {
            console.error(`Failed to load module "./modules/${handler}":`, err);
          });
      });
    }

    // Initialize UserContent module if we're on a user content page
    if (document.querySelector('.user-content')) {
        new UserContent();
    }
});

function getTSModules(): string[] {
    const elements = Array.from(document.querySelectorAll<HTMLElement>('[class]'));
    const classSet = new Set<string>();

    for (const el of elements) {
      const classes = el.className.split(/\s+/);
      for (const cls of classes) {
        if (cls.startsWith('ts-module-')) {
          classSet.add(cls);
        }
      }
    }

    return Array.from(classSet);
}