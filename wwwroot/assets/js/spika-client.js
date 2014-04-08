function SpikaClient(apiEndPointUrl)
{
    this.apiEndPointUrl = apiEndPointUrl;
    this.currentUser = null;
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
        succeessListener(data);
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

SpikaClient.prototype.postTextMessageToGroup = function(groupId,message,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'to_group_id':groupId,'message_type':'text','body':message};

    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/sendMessageToGroup',
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

SpikaClient.prototype.postTextMessageToUser = function(userId,message,succeessListener,failedListener)
{
    
    if(this.currentUser == null)
        return null;
    
    var postData = {'to_user_id':userId,'message_type':'text','body':message};

    var requestLogin = $.ajax({
        url: this.apiEndPointUrl + '/sendMessageToUser',
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

