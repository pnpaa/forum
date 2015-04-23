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
  tinymce.create('tinymce.plugins.FEImageUploadPlugin', {
    init : function(ed, url) {

      // Register commands
      ed.addCommand('feOpenModal', function() {
        jQuery('#uploadImgModal').modal('show');

        var $images_upload  = jQuery('#images_upload_container');
        //if(!$images_upload.hasClass('disabled')){
            var   TinyMCEUpload   = new ImagesUpload({
                            el                  : $images_upload,
                            uploaderID          : 'images_upload',
                            multi_selection     : false,
                            unique_names        : false,
                            upload_later        : true,
                            filters             : [
                                {title:"Image Files",extensions:'gif,jpg,png'},
                            ],
                            multipart_params    : {
                                _ajax_nonce : $images_upload.find('.et_ajaxnonce').attr('id'),
                                action      : 'et_upload_images'
                            },
                           
                            cbAdded         : function(up,files){
                                var i;

                                if(up.files.length > 1){
                                    while(up.files.length > 1){
                                        up.removeFile(up.files[0]);
                                    }
                                }

                                for( i=0; i < up.files.length; i++ ){
                                    jQuery("span.filename").text(up.files[i].name);
                                }
                                // console.log(up);
                            },

                            cbUploaded      : function(up,file,res){
                                if(res.success){
                                    tinyMCE.activeEditor.execCommand('mceInsertContent',false,"[img]" + res.data+"[/img]");
                                    jQuery('#uploadImgModal').modal('hide');
                                    jQuery("span.filename").text("No file chosen");   
                                    up.splice();
                                    up.refresh();
                                    up.destroy();                                
                                }
                                else {
                                    console.log(res);
                                    jQuery('#uploadImgModal').modal('hide');
                                    jQuery("span.filename").text("No file chosen");   
                                    up.splice();
                                    up.refresh();
                                    up.destroy(); 
                                    jQuery("button#insert").prop('disabled', false);                                   
                                    pubsub.trigger('fe:showNotice', res.msg , 'error');
                                }
                            },

                            onError     : function(up, err){
                                //console.log(err);
                            },
                            beforeSend: function(){
                                jQuery("button#insert").prop('disabled', true);
                            },
                            success : function(){
                                jQuery("button#insert").prop('disabled', false);
                            }
                        });
        //}

        //destroy plupload & set external link blank
        jQuery("button.close , span.btn-cancel").click(function(){

            //if(!$images_upload.hasClass('disabled')){            
                TinyMCEUpload.controller.splice();
                TinyMCEUpload.controller.refresh();
                TinyMCEUpload.controller.destroy();
            //}
            
            jQuery("input#external_link").val("");  
            jQuery("span.filename").text("No file chosen");                 
        });

        //insert link to current TinyMCE
        jQuery("button#insert").click(function(e){
            e.preventDefault();
            
            var input = jQuery("input#external_link");

            if(ForumEngine.app.currentUser.get('id') === 0 && input.val() == "" )
                return false;

            //if(!$images_upload.hasClass('disabled')){
                if(TinyMCEUpload.controller.files.length > 0){
                    hasUploadError = false; 
                    TinyMCEUpload.controller.start();
                }
            //}

            if(input.val() != ""){ 
                tinyMCE.activeEditor.execCommand('mceInsertContent',false,"[img]"+ input.val() +"[/img]");
                jQuery('#uploadImgModal').modal('hide');

                //if(!$images_upload.hasClass('disabled')){
                    TinyMCEUpload.controller.splice();
                    TinyMCEUpload.controller.refresh();
                    TinyMCEUpload.controller.destroy();  
                //}

                jQuery("input#external_link").val("");
                jQuery("span.filename").text(fe_front.texts.no_file_choose);
            }     
            
        });

      });

      // Register buttons
      ed.addButton('feimage', {
        title : fe_front.texts.upload_images,
        //class: 'feimage-icon',
        image : url + '/img/upload-image.gif',
        cmd : 'feOpenModal'
      });
    },
    getInfo : function() {
      return {
        longname : 'FE Image Upload',
        author : 'thaint',
        version : '0.0.1'
      };
    }
  });

  // Register plugin
  tinymce.PluginManager.add('feimage', tinymce.plugins.FEImageUploadPlugin);
})();
