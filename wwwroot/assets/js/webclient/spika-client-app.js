(function() {
    
    $(document).ready(function() {
    
        alertManager.showLoading();
        
        windowManager.init(window);
        _chatManager.init();
        
        // login
        _spikaClient.login(_loginedUser.email,_loginedUser.password,function(data){
            
            _loginedUser = data;
            _spikaClient.setCurrentUser(_loginedUser);
                        
            navigationBarManager.renderContacts();
            navigationBarManager.renderGroups();
            
            newMessageChecker.startUpdating();
            
            if(_targetUserId != 0){
                _chatManager.startPrivateChat(_targetUserId);                
            }else if(_targetGroupId != 0){
                _chatManager.startGroupChat(_targetGroupId);                
            }
            
            stickerViewManager.render();

        },function(errorString){
        
            alertManager.showAlert(_lang.labelErrorDialogTitle,_lang.messageTokenError,_lang.labelCloseButton,function(){
                location.href = "login";
            });
            
        });
        
        $('#btn-chat-send').click(function(){
            
            if(!_chatManager.isInConversation()){
                return;
            }
            
            _chatManager.sendTextMessage($('#textarea').val());
            $('#textarea').val('');
            $('#btn-chat-send').html('<i class="fa fa-refresh fa-spin"></i> Sending');
            $('#btn-chat-send').attr('disabled','disabled');
            
        });
        
        $('#btn_text').click(function(){
            $('#textarea').css('display','block');
            $('#sticker').css('display','none');
            $('#fileupload').css('display','none');
             
        });
        $('#btn_sticker').click(function(){
            $('#textarea').css('display','none');
            $('#sticker').css('display','block');
            $('#fileupload').css('display','none');
             
        });
        $('#btn_file').click(function(){
            $('#textarea').css('display','none');
            $('#sticker').css('display','none');
            $('#fileupload').css('display','block');
            $('#fileupload-box').css('display','block');
            $('#fileuploading').css('display','none');
        });
        
        // file dropzone setup
        var dropZone = document.getElementById('fileupload-box');
        dropZone.addEventListener('dragleave', fileUploadManager.handleDragLeave, false);
        dropZone.addEventListener('dragover', fileUploadManager.handleDragOver, false);
        dropZone.addEventListener('drop', fileUploadManager.handleFileSelect , false);
        
    });

})();
