var _spikaApp = {

    initApp : function(){
    
        alertManager.showLoading();
        
        windowManager.init(window);
        _chatManager.init();
        mediaViewManager.init();
        
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

        // file dropzone setup
        var dropZone = document.getElementById('fileupload-box');
        dropZone.addEventListener('dragleave', fileUploadManager.handleDragLeave, false);
        dropZone.addEventListener('dragover', fileUploadManager.handleDragOver, false);
        dropZone.addEventListener('drop', fileUploadManager.handleFileSelect , false);
        
    },
    showMediaView : function(messageId){

        $('#chat-view').css('display','none');
        $('#media-view').css('display','block');
        
        $('#submenu .submenubutton').each(function(){
            $(this).css('background-color','#f8f8f8');
        });
        
        $('#tab-media-view').css('background-color','#e7e7e7');
        
        mediaViewManager.loadMedia(messageId);
        
    },
    showChatView : function(){

        $('#chat-view').css('display','block');
        $('#media-view').css('display','none');
          
        $('#submenu .submenubutton').each(function(){
            $(this).css('background-color','#f8f8f8');
        });  
        
        $('#tab-chat-view').css('background-color','#e7e7e7');
    },
    
};

(function() {
    
    $(document).ready(function() {
        _spikaApp.initApp();
    });

})();
