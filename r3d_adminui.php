<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.r3d_adminui
 * @creation    2025-09-04
 * @author      Richard Dvorak, r3d.de
 * @copyright   Copyright (C) 2025 Richard Dvorak, https://r3d.de
 * @license     GNU GPL v3 or later (https://www.gnu.org/licenses/gpl-3.0.html)
 * @version     5.0.1
 * @file        plugins/system/r3d_adminui/r3d_adminui.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

final class PlgSystemR3d_adminui extends CMSPlugin
{
    protected $app;

    public function onBeforeCompileHead(): void
    {
        if (!$this->app->isClient('administrator')) {
            return;
        }

        $input = $this->app->getInput();
        if ($input->getCmd('option') !== 'com_modules' || $input->getCmd('view') !== 'module') {
            return;
        }

        // If editing an existing module, ensure it's our module type
        $id = $input->getInt('id');
        if ($id) {
            $db    = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('module'))
                ->from('#__modules')
                ->where('id = ' . (int) $id);
            $db->setQuery($query);
            if ((string) $db->loadResult() !== 'mod_r3d_pannellum') {
                return;
            }
        }

        $js = <<<JS
(function(){
  function norm(t){ return (t||'').toLowerCase().replace(/\\s+/g,' ').trim(); }

  // Accept EN/DE titles
  var titlesInter = ['intermediate settings','intermediate','intermediateeinstellungen','intermediate-einstellungen'];
  var titlesAdv   = ['advanced settings','advanced','erweiterte einstellungen','advanced-einstellungen'];

  function isInterTitle(t){ t = norm(t); return titlesInter.some(x => t === x); }
  function isAdvTitle(t){ t = norm(t); return titlesAdv.some(x => t === x); }

  function findSetupSelect(){
    return document.querySelector('[name="jform[params][setup_level]"]');
  }

  function getTabs(){
    var anchors = Array.prototype.slice.call(document.querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]'));
    return anchors.map(function(a){
      var txt = a.textContent || a.innerText || '';
      var paneId = a.getAttribute('aria-controls') || (a.getAttribute('href')||'').replace('#','');
      var li = a.closest('li') || a.parentElement;
      var pane = paneId ? document.getElementById(paneId) : null;
      return {a:a, li:li, pane:pane, text:txt};
    });
  }

  function firstVisibleTabLink(){
    var anchors = document.querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]');
    for (var i=0;i<anchors.length;i++){
      var a = anchors[i];
      var li = a.closest('li') || a.parentElement;
      if (!li) continue;
      if (li.style.display !== 'none') return a;
    }
    return null;
  }

  function toggleTabs(){
    var sel = findSetupSelect();
    if (!sel) return;
    var val = sel.value;

    var tabs = getTabs();
    tabs.forEach(function(t){
      var hide = false;
      if (val === 'basic') {
        hide = isInterTitle(t.text) || isAdvTitle(t.text);
      } else if (val === 'intermediate') {
        hide = isAdvTitle(t.text);
      } else { // advanced
        hide = false;
      }

      if (t.li)   t.li.style.display = hide ? 'none' : '';
      if (t.pane) t.pane.style.display = hide ? 'none' : '';

      // If we hide the active tab, jump to first visible
      var isActive = t.a.classList.contains('active') || t.a.getAttribute('aria-selected') === 'true';
      if (hide && isActive) {
        var first = firstVisibleTabLink();
        if (first && typeof first.click === 'function') first.click();
      }
    });
  }

  function setup(){
    var sel = findSetupSelect();
    if (!sel) return;
    sel.addEventListener('change', toggleTabs);
    toggleTabs();

    // Re-run if the tabs area changes (defensive)
    var nav = document.querySelector('.nav-tabs');
    if (nav && window.MutationObserver) {
      var mo = new MutationObserver(function(){ setTimeout(toggleTabs, 0); });
      mo.observe(nav, {childList:true, subtree:true, attributes:true});
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Sometimes the form takes a tick to initialize
    setTimeout(setup, 0);
    setTimeout(toggleTabs, 200);
    setTimeout(toggleTabs, 600);
  });
})();
JS;

        Factory::getDocument()->addScriptDeclaration($js);
    }
}
JS;

        Factory::getDocument()->addScriptDeclaration($js);
    }
}
