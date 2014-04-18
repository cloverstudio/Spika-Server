    
    // handles modal dialogs
    var alertManager = {

        showAlert : function(title,message,buttonText,onClose){
            
            $('#modalAlertDialog #modalTitle').text(title);
            $('#modalAlertDialog #modalText').text(message);
            $('#modalAlertDialog #modalDismissButton').text(buttonText);
            
            $('#modalAlertDialog').modal('show');
            $('#modalAlertDialog').unbind('hide.bs.modal');
            $('#modalAlertDialog').on('hide.bs.modal', function (e) {
                onClose();
            })
        },
        showError : function(message){
            
            $('#modalAlertDialog #modalTitle').text(_lang.labelErrorDialogTitle);
            $('#modalAlertDialog #modalText').text(message);
            $('#modalAlertDialog #modalDismissButton').text(_lang.labelCloseButton);
            
            $('#modalAlertDialog').modal('show');
        },
        showLoading : function(){
            $('#modalLoading').modal('show');
        },
        hideLoading : function(){
            $('#modalLoading').modal('hide');
        },
        showPasswordDialog : function(onEnter,defaultValue){
            
            $('#passwordInputDialog #modalDismissButton').text('OK');
            $('#passwordInputDialog #modalTitle').text('Please input password');
            
            if(_.isUndefined(defaultValue)){
                $('#passwordField').val('');  
            }else{
                $('#passwordField').val(defaultValue);            
            }
            
            $('#passwordInputDialog').modal('show');
            $('#passwordInputDialog').unbind('hide.bs.modal');
            $('#passwordInputDialog').on('hide.bs.modal', function (e) {
                var password = $('#passwordField').val();
                onEnter(password);
            })
            
        }
            
    };
    