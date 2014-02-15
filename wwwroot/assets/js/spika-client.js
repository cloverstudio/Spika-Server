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
    
    var request = $.ajax({
        url: this.apiEndPointUrl + '/userMessages/' + toUserId + '/' + count + '/' + page,
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
