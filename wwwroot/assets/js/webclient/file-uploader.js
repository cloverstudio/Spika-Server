   
    var fileUploadManager = {
        handleFileSelect : function(event){
            
            event.preventDefault();
            
            var files = event.dataTransfer.files;
            
            if(_.isUndefined(files)){
                return;
            }
            
            var filesCount = files.length;
            
            if(filesCount > 1){
                alertManager.showError(_lang.messageValidationErrorTooManyFiles);
                return;
            }
            
            var file = files[0];
            var fileType = file.type;
            
            if(fileType != 'image/jpeg' && fileType != 'image/pjpeg' && fileType != 'video/mp4' && fileType != 'audio/mp3' && fileType != 'audio/mpeg'){
                alertManager.showError(_lang.messageValidationErrorWrongFileType);
                return;
            }
            
            if(!_chatManager.isInConversation()){
                return;
            }
            
            // upload
            $('#fileupload-box').css('display','none');
            $('#fileuploading').css('display','block');
            
            $('#btn-chat-send').attr('disabled','disabled');
            
            
            if(fileType == 'image/jpeg' || fileType == 'image/pjpeg'){
                _chatManager.sendMediaMessage(file,_spikaClient.MEDIA_TYPE_IMAGE,function(){
                    $('#fileupload-box').css('display','block');
                    $('#fileuploading').css('display','none');
                });
            }

            if(fileType == 'video/mp4'){
                _chatManager.sendMediaMessage(file,_spikaClient.MEDIA_TYPE_VIDEO,function(){
                    $('#fileupload-box').css('display','block');
                    $('#fileuploading').css('display','none');
                });
            }
                        
            if(fileType == 'audio/mp3' || fileType == 'audio/mpeg'){
                _chatManager.sendMediaMessage(file,_spikaClient.MEDIA_TYPE_AUDIO,function(){
                    $('#fileupload-box').css('display','block');
                    $('#fileuploading').css('display','none');
                });
            }
                        
        },
        handleDragOver : function(event){
            event.preventDefault();
            $('#fileupload-box').css('border-color','#f88');
        },
        handleDragLeave : function(event){
            event.preventDefault();
            $('#fileupload-box').css('border-color','#888');
        }
    };