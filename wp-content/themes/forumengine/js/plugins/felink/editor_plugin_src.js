/**
 * editor_plugin_src.js
 *
 * Copyright 2013, EngineTheme Team
 * Released under LGPL License.
 *
 * License: http://www.enginethemes.com/
 * Contributing: http://www.enginethemes.com/
 */

(function() {
  tinymce.create('tinymce.plugins.FELink', {
    init : function(ed, url) {

      var disabled = true;

      // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
      ed.addCommand('FE_Link', function() {
        if ( disabled )
          return;
        ed.windowManager.open({
          id : 'wp-link-1',
          width : 480,
          height : "auto",
          wpDialog : true,
          title : ed.getLang('advlink.link_desc')
        }, {
          plugin_url : url // Plugin absolute URL
        });
        jQuery("input#url-field").focus();
      });

      // Register buttons
      ed.addButton('felink', {
        title : fe_front.texts.insert_link,
        'class': 'mce_link',
        cmd : 'FE_Link'
      });

      ed.onNodeChange.add(function(ed, cm, n, co) {
        disabled = co && n.nodeName != 'A';
        cm.get( 'felink' ).setDisabled( ed.selection.isCollapsed() );
      });
     
    },
    getInfo : function() {
      return {
        longname : 'FE Link Insert',
        author : 'thaint',
        version : '0.0.1'
      };
    }
  });

  // Register plugin
  tinymce.PluginManager.add('felink', tinymce.plugins.FELink);
})();
