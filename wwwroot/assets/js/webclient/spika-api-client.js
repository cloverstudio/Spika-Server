function SpikaClient(apiEndPointUrl)
{
    this.apiEndPointUrl = apiEndPointUrl;
    this.currentUser = null;
    
    this.MESSAGE_TAEGET_USER = 'user';
    this.MESSAGE_TAEGET_GROUP = 'group';

    this.MEDIA_TYPE_IMAGE = 'image';
    this.MEDIA_TYPE_VIDEO = 'video';
    this.MEDIA_TYPE_AUDIO = 'voice';
    
}

SpikaClient.prototype.setRenderer = function(renderer)
{
    this.renderer = renderer;
}

SpikaClient.prototype.getRenderer = function()
{
    return this.renderer;
}


// Login
SpikaClient.prototype.login = function(userName,password,succeessListener,failedListener)
{
    // login
    var postData = {'email':userName,'password':password};

    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/auth',
        type: 'POST',
        dataType:'json',
        data:JSON.stringify(postData)
    });
    
    requestLogin.done(function( data ) {
        
        if(data.error != null && data.error != ''){
            failedListener(data);
        } else {
            succeessListener(data);
        }
        
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.setCurrentUser = function(user)
{
    this.currentUser = user;
}

SpikaClient.prototype.loadUserChat = function(toUserId,count,page,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var offset = count * (page - 1);
    var request = $.ajax({
        url: this.apiEndPointUrl + '/userMessages/' + toUserId + '/' + count + '/' + offset,
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.loadGroupChat = function(groupId,count,page,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var offset = count * (page - 1);
    var request = $.ajax({
        url: this.apiEndPointUrl + '/groupMessages/' + groupId + '/' + count + '/' + offset,
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.postTextMessage = function(type,targetId,message,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'message_type':'text','body':message};
    var url = "";
    
    if(type == this.MESSAGE_TAEGET_USER){
        postData.to_user_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToUser';
    }
    if(type == this.MESSAGE_TAEGET_GROUP){
        postData.to_group_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToGroup';
    }

    var requestLogin = $.ajax({
        url: url,
        type: 'POST',
        dataType:'json',
        data:JSON.stringify(postData),
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.postMediaMessage = function(type,mediaType,targetId,fileId,thumbFileId,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'body':''};
    var url = "";
    
    if(type == this.MESSAGE_TAEGET_USER){
        postData.to_user_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToUser';
    }
    if(type == this.MESSAGE_TAEGET_GROUP){
        postData.to_group_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToGroup';
    }

    if(mediaType == this.MEDIA_TYPE_IMAGE){
        postData.message_type = 'image';
        postData.picture_file_id = fileId;
        postData.picture_thumb_file_id = thumbFileId;
    }
    
    if(mediaType == this.MEDIA_TYPE_VIDEO){
        postData.message_type = 'video';
        postData.video_file_id = fileId;
    }
    
    if(mediaType == this.MEDIA_TYPE_AUDIO){
        postData.message_type = 'voice';
        postData.voice_file_id = fileId;
    }
    
    var requestLogin = $.ajax({
        url: url,
        type: 'POST',
        dataType:'json',
        data:JSON.stringify(postData),
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.postStickerMessage = function(type,targetId,stickerIdentifier,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'message_type':'emoticon','body':stickerIdentifier};
    var url = "";
    
    if(type == this.MESSAGE_TAEGET_USER){
        postData.to_user_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToUser';
    }
    if(type == this.MESSAGE_TAEGET_GROUP){
        postData.to_group_id = targetId;
        url = this.apiEndPointUrl + '/sendMessageToGroup';
    }

    console.log(postData);
    
    var requestLogin = $.ajax({
        url: url,
        type: 'POST',
        dataType:'json',
        data:JSON.stringify(postData),
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}



SpikaClient.prototype.getActivitySummary = function(succeessListener,failedListener)
{
    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/activitySummary',
        type: 'GET',
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        
        failedListener(jqXHR.responseText);
        
    });

}


SpikaClient.prototype.getUser = function(userId,succeessListener,failedListener)
{
    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/findUser/id/' + userId,
        type: 'GET',
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}


SpikaClient.prototype.getGroup = function(groupId,succeessListener,failedListener)
{
    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/findGroup/id/' + groupId,
        type: 'GET',
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.getContacts = function(succeessListener,failedListener)
{

    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/getContacts',
        type: 'GET',
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.getFavoriteGroups = function(succeessListener,failedListener)
{

    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/getFavoriteGroups',
        type: 'GET',
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    requestLogin.done(function( data ) {
        succeessListener(data);
    });
    
    requestLogin.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.checkUpdate = function(succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var offset = count * (page - 1);
    var request = $.ajax({
        url: this.apiEndPointUrl + '/checkUpdate',
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.fileUpload = function(file,succeessListener,failedListener)
{
    // login
    var formData = new FormData();
    formData.append('file', file);
       
    var request = $.ajax({
        url: this.apiEndPointUrl + '/fileuploader',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.loadStickers = function(succeessListener,failedListener)
{

    var request = $.ajax({
        url: this.apiEndPointUrl + '/Emoticons',
        type: 'GET',
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

// delete
SpikaClient.prototype.setDelete = function(messageId,deleteType,succeessListener,failedListener)
{

    var postData = {'message_id':messageId,'delete_type':deleteType};

    var request = $.ajax({
        url: this.apiEndPointUrl + '/setDelete',
        type: 'POST',
        data:JSON.stringify(postData),
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.getMessage = function(messageId,succeessListener,failedListener)
{

    var request = $.ajax({
        url: this.apiEndPointUrl + '/findMessageById/' + messageId,
        type: 'GET',
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.getMediaComments = function(messageId,succeessListener,failedListener)
{

    var request = $.ajax({
        url: this.apiEndPointUrl + '/comments/' + messageId + "/1000/0",
        type: 'GET',
        type: "GET",
        dataType:'json',
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

SpikaClient.prototype.postComment = function(messageId,comment,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'message_id':messageId,'comment':comment};
    var url = this.apiEndPointUrl + '/sendComment';
    
    var request = $.ajax({
        url: url,
        type: 'POST',
        dataType:'json',
        data:JSON.stringify(postData),
        headers: { 'token': this.currentUser.token }
    });
    
    request.done(function( data ) {
        succeessListener(data);
    });
    
    request.fail(function( jqXHR, textStatus ) {
        failedListener(jqXHR.responseText);
    });

}

