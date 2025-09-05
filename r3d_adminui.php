<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.r3d_adminui
 * @creation    2025-09-04
 * @author      Richard Dvorak, r3d.de
 * @copyright   Copyright (C) 2025 Richard Dvorak, https://r3d.de
 * @license     GNU GPL v3 or later (https://www.gnu.org/licenses/gpl-3.0.html)
 * @version     5.0.0
 * @file        plugins/system/r3d_adminui/r3d_adminui.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Admin-side UI helpers for R3D extensions.
 * Hides Intermediate/Advanced tabs on the mod_r3d_pannellum edit form based on Setup Level.
 */
final class PlgSystemR3d_adminui extends CMSPlugin
{
    protected $app;

    public function onBeforeCompileHead(): void
    {
        // Only in administrator app
        if (!$this->app->isClient('administrator')) {
            return;
        }

        $input = $this->app->getInput();

        // Only on module edit view
        if ($input->getCmd('option') !== 'com_modules' || $input->getCmd('view') !== 'module') {
            return;
        }

        // Ensure it's our module (mod_r3d_pannellum)
        $id = $input->getInt('id');
        if ($id) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('module'))
                ->from('#__modules')
                ->where('id = ' . (int) $id);
            $db->setQuery($query);
            if ((string) $db->loadResult() !== 'mod_r3d_pannellum') {
                return;
            }
        }

        // Inject small script to toggle tabs based on Setup Level
        $js = <<<JS
document.addEventListener('DOMContentLoaded', function(){
  function toggleTabs(){
    var sel = document.querySelector('[name="jform[params][setup_level]"]');
    if(!sel) return;
    var val = sel.value;

    document.querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]').forEach(function(a){
      var ctrl = a.getAttribute('aria-controls') || (a.getAttribute('href')||'').replace('#','');
      var li = a.closest('li') || a.parentElement;
      if(!ctrl || !li) return;

      var isInter = /params[_-]intermediate/i.test(ctrl);
      var isAdv   = /params[_-]advanced/i.test(ctrl);

      var show = true;
      if(val === 'basic'){ if(isInter || isAdv) show = false; }
      else if(val === 'intermediate'){ if(isAdv) show = false; }

      li.style.display = show ? '' : 'none';

      if(!show && (a.classList.contains('active') || a.getAttribute('aria-selected') === 'true')){
        var firstVisible = document.querySelector('.nav-tabs a[data-bs-toggle="tab"]:not([style*="display: none"])');
        if(firstVisible) firstVisible.click();
      }
    });
  }
  var sel = document.querySelector('[name="jform[params][setup_level]"]');
  if(sel){
    sel.addEventListener('change', toggleTabs);
    setTimeout(toggleTabs, 0);
    setTimeout(toggleTabs, 250);
    setTimeout(toggleTabs, 600);
  }
});
JS;

        Factory::getDocument()->addScriptDeclaration($js);
    }
}
