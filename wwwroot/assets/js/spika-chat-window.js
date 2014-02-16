function SpikaChatWindow(apiEndPointUrl,user,lang,enableSending,chatMode)
{ 
    this.templateBaseHtml = '<div id="chat-window-top" class="chat-panel panel panel-default"><div class="panel-heading"><span id="chat-window-header">&nbsp;</span> <div class="btn-group pull-right"><button id="btnReload" type="button" class="btn btn-primary btn-xs" disabled="disabled">%%btnReload%%</button> </div></div><div id="chatbox" class="panel-body"><ul id="chat-window" class="chat"></ul></div></div>'
    
    this.templateAlert = '<div class="alert alert-danger alert-dismissable">%%alertmessage%%</div>'
    
    this.templateLeftRow = '<li class="left clearfix"><span class="chat-img pull-left">%%avatarImage%%</span><div class="chat-body clearfix"><div class="header"><strong class="primary-font">%%name%%</strong> <small class="pull-right text-muted"><i class="fa fa-clock-o fa-fw"></i>%%time%%</small></div><p style="text-align:right">%%message%%</p></div></li>';
    
    this.templateRightRow = '<li class="right clearfix"><span class="chat-img pull-right"> %%avatarImage%%</span><div class="chat-body clearfix"><div class="header"><small class=" text-muted"><i class="fa fa-clock-o fa-fw"></i> %%time%%</small><strong class="pull-right primary-font">%%name%%</strong></div><p style="text-align:left">%%message%%</p></div></li>';
    
    this.templateAvatarImage = '<img src="%%imageURL%%" alt="User Avatar" class="img-circle" width="50"/>';

    this.templateImageMessage = '<a class="img-thumbnail" data-toggle="modal" data-target=".bs-example-modal-lg%%id%%"><img src="%%ThumbUrl%%" width="150"/></a><div class="modal fade bs-example-modal-lg%%id%%" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><img src="%%ImageUrl%%"/></div></div></div>'
    
    this.templateTextBox = '<div class="panel-footer"><div id="media-box"></div><div class="input-group"><span class="input-group-btn"><button id="btn-tuggl-mediabox" class="btn btn-default btn-sm" type="button"><i class="fa fa-chevron-down"></i></button></span><input id="btn-input" type="text" class="form-control input-sm" placeholder="%%chatTextBoxPlaceHolder%%"><span class="input-group-btn"><button class="btn btn-warning btn-sm" id="btn-chat">%%chatTextBoxSend%%</button></span></div></div>';
    
    this.templateMediaPanel = '<div class="media-buttons"><button id="mediabtn-emoji" type="button" class="btn btn-outline btn-default"><i class="fa fa-smile-o fa-4x"></i></button><button id="mediabtn-picture" type="button" class="btn btn-outline btn-default"><i class="fa fa-picture-o fa-4x"></i></button></div>';
    
    this.spikaClient = new SpikaClient(apiEndPointUrl);
    this.lang = lang;
    this.currentPage = 0;
    this.rows = 30;
    this.apiEndPointUrl = apiEndPointUrl;
    this.lastUserId = 0;
    this.lastGroupId = 0;
    this.isLastPage = false;
    this.isLoading = false;
    this.chatMode = 0; // 1:private chat 2:group chat
    this.enableSending = enableSending;
    this.mediaboxOpened = false;
    
    if(chatMode != undefined){
        this.chatMode = chatMode;
    }
    
    this.setUser(user);
}


SpikaChatWindow.prototype.initialize = function()
{
    this.currentPage = 0;
    this.lastUserId = 0;
    this.lastGroupId = 0;
    this.isLastPage = false;
    $('#chat-window').html('');
}

SpikaChatWindow.prototype.setUser = function(user)
{
    this.spikaClient.setCurrentUser(user);
    this.user = user;
}

SpikaChatWindow.prototype.attach = function(htmlElement,title)
{
    var html = this.templateBaseHtml;
    
    html = html.replace(/%%title%%/,this.lang.title);
    html = html.replace(/%%btnReload%%/,this.lang.btnReload);
    
    $(htmlElement).html(html);
    
    if(this.enableSending){
        var textBoxHtml = this.templateTextBox;
        textBoxHtml = textBoxHtml.replace(/%%chatTextBoxPlaceHolder%%/,this.lang.chatTextBoxPlaceHolder);
        textBoxHtml = textBoxHtml.replace(/%%chatTextBoxSend%%/,this.lang.chatTextBoxSend);
        
        $('#chat-window-top').append(textBoxHtml);
        
        // build media box panel
        this.buildMediaBox();
        
        $('#btn-chat').unbind("click");
        $('#btn-tuggl-mediabox').unbind("click");
        $('#mediabtn-emoji').unbind("click");
        $('#mediabtn-picture').unbind("click");
        
        var self = this;
        $('#btn-chat').click(function(){
            
            var text = $('#btn-input').val();
            
            if(text == '' || self.chatMode == 0)
                return;
             
            if(self.chatMode == 1){
            
                if(self.lastUserId == 0)
                    return;

                self.spikaClient.postTextMessageToUser(self.lastUserId,text,function(data){
                    
                    var userId = self.lastUserId;

                    self.initialize();
                    
                    self.lastUserId = userId;
                    
                    self.loadUserConversation(self.lastUserId);
                    
                },function(errorString){
                    
                    alert('Failed to post message');
                    self.hideLoading();
                    
                });
                
            } 
            
            if(self.chatMode == 2){
            
                if(self.lastGroupId == 0)
                    return;

                self.spikaClient.postTextMessageToGroup(self.lastGroupId,text,function(data){
                    
                    var groupId = self.lastGroupId;
                    
                    self.initialize();
                    
                    self.lastGroupId = groupId;
                    
                    self.loadGroupConversation(self.lastGroupId);
                    
                },function(errorString){
                    
                    alert('Failed to post message');
                    self.hideLoading();
                    
                });
                
            } 
            

            
        });
        
        $('#btn-tuggl-mediabox').click(function(){
            self.tuggleMediaBox();
        });
    }
    
    var self = this;

}

SpikaChatWindow.prototype.buildMediaBox = function(message)
{
    $('#media-box').html(this.templateMediaPanel); 
    $('#media-box').hide();

    $('#mediabtn-emoji').click(function(){
        
        
        
    });
    
    $('#mediabtn-picture').click(function(){
        
        
        
    });
}

SpikaChatWindow.prototype.tuggleMediaBox = function(message)
{
            
    if(this.mediaboxOpened == false){
        $('#media-box').show();
        this.mediaboxOpened = true;     
        $('#btn-tuggl-mediabox').html('<i class="fa fa-chevron-up "></i>');
               
    }else{
        $('#media-box').hide();
        this.mediaboxOpened = false;                
        $('#btn-tuggl-mediabox').html('<i class="fa fa-chevron-down "></i>');           
    }
}


SpikaChatWindow.prototype.showAlert = function(message)
{
    var html = this.templateAlert;
    html = html.replace(/%%alertmessage%%/,message);
    $('#chat-window').html(html);
}

SpikaChatWindow.prototype.showLoading = function()
{
    $('<div class="loader" style="color: #fff;text-align:center;"><i style="position: relative;top: 40%;" class="fa fa-spinner fa-spin fa-5x"></i></div>').css({
        position: "absolute",
        width: "100%",
        height: "100%",
        top: 0,
        left: 0,
        background: "#101010",
        opacity: 0.1
    }).appendTo($("#chatbox").parent().css("position", "relative"));
}

SpikaChatWindow.prototype.hideLoading = function()
{
    $('.loader').remove();
}

SpikaChatWindow.prototype.loadGroupConversation = function(groupId)
{
    this.isLoading = true;
    this.chatMode = 2;
    
    var self = this;
    this.lastGroupId = groupId;
    this.showLoading();
    
    var offset = this.currentPage  * this.rows;
    
    if(this.currentPage == 0)
        $('#chat-window').html('');

    this.spikaClient.getGroup(groupId,function(data){
        console.log(data);
        $('#chat-window-header').html('<span class="btn btn-warning btn-circle"><i class="fa fa-users"></i></span>  <strong>' + data.name + '</strong>');
        
    },function(errorString){
    });
    
    this.spikaClient.loadGroupChat(groupId,this.rows,offset,function(data){
        
        self.renderConversation(data);
        self.isLoading = false;
        self.hideLoading();
        
    },function(errorString){
        
        if(errorString.match(/expired|invalid/i)){
            
            self.spikaClient.login(self.user.email,self.user.password,function(data){

                self.setUser(data);
                self.spikaClient.setCurrentUser(self.user);
                self.loadGroupConversation(toUserId);
            
            },function(errorString){
            
                console.log(errorString);
                self.hideLoading();
                self.isLoading = false;
            });
            
        }
        
        self.isLoading = false;
        self.hideLoading();
        
    });
}


SpikaChatWindow.prototype.loadUserConversation = function(toUserId)
{
    this.isLoading = true;
    this.chatMode = 1;
    
    var self = this;
    this.lastUserId = toUserId;
    this.showLoading();
    
    var offset = this.currentPage  * this.rows;
    
    if(this.currentPage == 0)
        $('#chat-window').html('');

    this.spikaClient.getUser(toUserId,function(data){
        console.log(data);
                            
        $('#chat-window-header').html('<span class="btn btn-warning btn-circle"><i class="fa fa-user"></i></span> <strong>' + data.name + '</strong>');
    },function(errorString){
    });
    
    this.spikaClient.loadUserChat(toUserId,this.rows,offset,function(data){
        
        self.renderConversation(data);
        self.isLoading = false;
        self.hideLoading();
        
    },function(errorString){
        
        if(errorString.match(/expired|invalid/i)){
            
            self.spikaClient.login(self.user.email,self.user.password,function(data){

                self.setUser(data);
                self.spikaClient.setCurrentUser(self.user);
                self.loadUserConversation(toUserId);
            
            },function(errorString){
            
                console.log(errorString);
                self.hideLoading();
                self.isLoading = false;
            });
            
        }
        
        self.isLoading = false;
        self.hideLoading();
        
    });
}

SpikaChatWindow.prototype.renderConversation = function(data)
{

    if(data.rows.length != this.rows)
        this.isLastPage = true;
        
    for(chatRowIndex in data.rows){
        var html = "";
        var chatData = data.rows[chatRowIndex].value;
        html = this.renderChatRow(chatData);
        $('#chat-window').prepend(html);
    }
    
    $('#btnReload').removeAttr('disabled');
    
    var self = this;
    
    $('#btnReload').unbind( "click" );
    
    $('#btnReload').click(function(){
    
        lastUserIdTmp = self.lastUserId;
        lastGroupIdTmp = self.lastGroupId;
        
        self.initialize();
        
        self.lastUserId = lastUserIdTmp;
        self.lastGroupId = lastGroupIdTmp;

        if(self.chatMode == 1)
            self.loadUserConversation(self.lastUserId);
        else
            self.loadGroupConversation(self.lastGroupId);

    });
    
    // scroll to bottom
    if(this.currentPage == 0)
        $('#chatbox').scrollTop($('#chatbox')[0].scrollHeight);

    
    $('#chatbox').scroll(function(){
 
        if(self.isLastPage)
            return;
            
        if(self.isLoading)
            return;
            
        if($('#chatbox').scrollTop() == 0){
            self.currentPage++;
            
            if(this.chatMode == 1)
                self.loadUserConversation(self.lastUserId);
            else
                self.loadGroupConversation(self.lastGroupId);
        }
    });
}

SpikaChatWindow.prototype.renderChatRow = function(chatRow){
        
        var templateChatRow = this.templateLeftRow;
        if(this.user._id == chatRow.from_user_id)
            templateChatRow = this.templateRightRow;
        
        var date = new Date(chatRow.created*1000);
        var formattedTime = (1900 + date.getYear()) + "/" + date.getMonth() + "/" + date.getDate() + " " + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds();

        templateChatRow = templateChatRow.replace(/%%name%%/,chatRow.from_user_name);
        templateChatRow = templateChatRow.replace(/%%message%%/,this.renderChatBody(chatRow));
        templateChatRow = templateChatRow.replace(/%%time%%/,formattedTime);
        
        if(chatRow.avatar_thumb_file_id != ""){
            templateAvatarImage = this.templateAvatarImage.replace(/%%imageURL%%/,this.apiEndPointUrl + '/filedownloader?file=' + chatRow.avatar_thumb_file_id);
        }else{
            templateAvatarImage = this.templateAvatarImage.replace(/%%imageURL%%/,'http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture');
        }

        templateChatRow = templateChatRow.replace(/%%avatarImage%%/,templateAvatarImage);
        
        return templateChatRow;
}


SpikaChatWindow.prototype.renderChatBody = function(chatRow){

        if(chatRow.message_type == 'text')
            return chatRow.body;
        
        if(chatRow.message_type == 'image'){
            var html = this.templateImageMessage;
            html = html.replace(/%%id%%/g,chatRow._id);
            html = html.replace(/%%ThumbUrl%%/,this.apiEndPointUrl + '/filedownloader?file=' + chatRow.picture_thumb_file_id);
            html = html.replace(/%%ImageUrl%%/,this.apiEndPointUrl + '/filedownloader?file=' + chatRow.picture_file_id);
            return html;
        }
        
        if(chatRow.message_type == 'emoticon'){
            var html = '<img src="' + chatRow.emoticon_image_url + '" width="150"/>';
            return html;
        }  
            
        if(chatRow.message_type == 'voice'){
            var html = '<a href="' + this.apiEndPointUrl + '/filedownloader?file=' + chatRow.voice_file_id + '" target="_blank"><i class="fa fa-microphone"></i> ' + chatRow.body + '</a>';
            return html;
        }  
            
        if(chatRow.message_type == 'video'){
            var html = '<a href="' + this.apiEndPointUrl + '/filedownloader?file=' + chatRow.video_file_id + '" target="_blank"><i class="fa fa-video-camera"></i> ' + chatRow.body + '</a>';
            return html;
        }  
        
        if(chatRow.message_type == 'location'){
            var html = '<a href="https://www.google.com/maps/@' + chatRow.latitude + ',' + chatRow.longitude + ',11z" target="_blank"><i class="fa fa-map-marker"></i> ' + this.lang.openLocation + '</a>';
            return html;
        }  
            
}



