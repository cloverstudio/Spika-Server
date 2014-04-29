
    // everything for caht
    _chatManager = {
        
        templateDate : _.template('<div class="timestamp_date"><p><%= date %></p></div>'),
        templateChatBlockPerson : _.template('<div class="post_block"><%= conversation %></div>'),
        templateUserInfo : _.template('<div class="person_info"><h5><%= img %><a target="_blank" href="' + _consts.RootURL + '/admin/user/view/<%= from_user_id %>"><%= from_user_name %></a></h5><div class="clear"></div></div>'),
        avatarImage : _.template('<img src="' + _consts.RootURL + '/api/filedownloader?file=<%= avatar_thumb_file_id %>" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        avatarNoImage : _.template('<img src="http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        templatePostHolder : _.template('<div class="post userId<%= user_id %>" id="message<%= message_id %>" messageid="<%= message_id %>"><div class="timestamp"><%= deleteicon %> <%= commentsicon %> <%= unreadicon %> <%= time %></div><%= content %></div>'),
        templateDeleteIcon : _.template('<span type="button" class="btn btn-link deleteIcon" data-toggle="tooltip" data-placement="left" title="<%= deletetime %>"><i class="fa fa-trash-o text-danger"></i></span>'),
        templateCommentsIcon : _.template('<i class="fa fa-comments-o"></i>'),
        templateUnreadIcon : _.template('<i class="fa fa-envelope-o"></i>'),
        templateTextPost : _.template('<div class="post_content" messageid="<%= _id %>"><%= body %></div>'),
        templatePicturePost : _.template('<div class="post_content" messageid="<%= _id %>"><a class="img-thumbnail" data-toggle="modal" data-target=".bs-example-modal-lg<%= _id  %>"><img src="' + _consts.RootURL + '/api/filedownloader?file=<%= picture_thumb_file_id %>" height="120" width="120" /></a></div></div><div class="modal fade bs-example-modal-lg<%= _id %>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><img src="' + _consts.RootURL + '/api/filedownloader?file=<%= picture_file_id %>" /></div></div>'),
        templatePicturePostMediaView : _.template('<div class="post_content" messageid="<%= _id %>"><a href="javascript:_spikaApp.showMediaView(<%= _id %>)"><img src="' + _consts.RootURL + '/api/filedownloader?file=<%= picture_thumb_file_id %>" height="120" width="120" /></a></div>'),
        templateEmoticonPost : _.template('<div class="post_content" messageid="<%= _id %>"><img src="<%= emoticon_image_url %>" height="120" width="120" /></div>'),
        templateVoicePost : _.template('<div class="fa fa-play-circle-o fa-4x post_fa"></div><div class="post_content post_media" messageid="<%= _id %>"><%= body %><br /><a target="_blank" href="javascript:_spikaApp.showMediaView(<%= _id %>)">' + _lang.listenVoice + '</a></div>'),
        templateVideoPost : _.template('<div class="fa fa-video-camera fa-4x post_fa"></div><div class="post_content post_media" messageid="<%= _id %>"><%= body %><br /><a target="_blank" href="javascript:_spikaApp.showMediaView(<%= _id %>)">' + _lang.watchVideo + '</a></div>'),
        templateLocationPost : _.template('<div class="fa fa-location-arrow fa-4x post_fa"></div><div class="post_content post_media" messageid="<%= _id %>"><%= body %><br /><a target="_blank" href="http://maps.google.com/?q=<%= latitude %>,<%= longitude %>">' + _lang.openInGoogleMap + '</a></div>'),
        chatPageRowCount : 30,
        chatCurrentPage : 1,
        chatCurrentUserId : 0,
        chatCurrentGroupId : 0,
        chatContentPool : [],
        isLoading : false,
        isReachedToEnd : false,
        init : function(){
            
            var self = this;
            this.chatContentPool = [];
            
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
                
                stickerViewManager.render();
                
            });
            $('#btn_file').click(function(){
                $('#textarea').css('display','none');
                $('#sticker').css('display','none');
                $('#fileupload').css('display','block');
                $('#fileupload-box').css('display','block');
                $('#fileuploading').css('display','none');
            });
            
            $("#conversation_block").scroll(function() {
                
                var scrollPosition = $(this).scrollTop();
                
                if(scrollPosition == 0){
                    
                    if(self.isLoading == false){
                        self.isLoading = true;
                        self.loadNextPage();
                    }else{
                        
                    }
                                        
                }
                                
            });

        },
        loadNextPage : function(){
            
            if(this.isReachedToEnd)
                return;
                
            var self = this;
            this.chatCurrentPage++;
            
            var lastHeight = $("#conversation_block")[0].scrollHeight;
            
            if(this.chatCurrentUserId != 0){
            
                _spikaClient.loadUserChat(this.chatCurrentUserId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                    self.mergeConversation(data);
                    self.render();
                    self.isLoading = false;
                    
                    var currentHeight = $("#conversation_block")[0].scrollHeight;
                                     
                    $("#conversation_block").scrollTop(currentHeight - lastHeight);
                    
                },function(errorString){
                    
                     _spikaApp.handleError(errorString,"loadUserChat");
                     
                });
                
            }
            
            else if(this.chatCurrentGroupId != 0){
                
                _spikaClient.loadGroupChat(this.chatCurrentGroupId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                    self.mergeConversation(data);
                    self.render();
                    self.isLoading = false;

                    var currentHeight = $("#conversation_block")[0].scrollHeight;
                    
                    $("#conversation_block").scrollTop(currentHeight - lastHeight);


                },function(errorString){
                    
                    _spikaApp.handleError(errorString,"loadGroupChat");
                    
                });
                
            }
            
        },
        resetChat : function(){
        
            console.log('reset chat');
            
            if(this.chatCurrentUserId != 0){
                
                this.startPrivateChat(this.chatCurrentUserId);
                
            } else if(this.chatCurrentGroupId != 0){
                
                this.startGroupChat(this.chatCurrentGroupId);
                
            }

        },
        startPrivateChat : function(userId){
            
            _spikaApp.showChatView();
            
            var self = this;
            
            alertManager.showLoading();
            
            this.chatCurrentUserId = userId;
            this.chatCurrentGroupId = 0;
            this.chatCurrentPage = 1;
            this.chatContentPool = [];
            
            self.isLoading = true;
            
            _spikaClient.loadUserChat(userId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                
                sideBarManager.renderUserProfile(userId);
                
                alertManager.hideLoading();

                self.mergeConversation(data);
            
                self.render();
                
                self.scrollToBottom();
                
                self.isLoading = false;
                
            },function(errorString){
            
                alertManager.hideLoading();
                _spikaApp.handleError(errorString,"loadUserChat");
            
            });
            
        },
        startGroupChat : function(groupId){
            
            _spikaApp.showChatView();
            
            var self = this;
            
            alertManager.showLoading();
            
            this.chatCurrentGroupId = groupId;
            this.chatCurrentUserId = 0;
            this.chatCurrentPage = 1;
            this.chatContentPool = [];
            
            self.isLoading = true;
            
            var cookieKey = "groupPassword" + groupId;
            
            // check password
            _spikaClient.getGroup(groupId,function(data){
                
                if(!_.isEmpty(data.group_password)){
        
                    var savedPassword = Cookies(cookieKey);                
                    
                    console.log(savedPassword);
                          
                    alertManager.showPasswordDialog(function(password){
                        
                        var hash = CryptoJS.MD5(password);
                        
                        console.log(hash.toString().toLowerCase());
                        console.log(data.group_password.toString().toLowerCase());
                        
                        if(hash.toString().toLowerCase() == data.group_password.toString().toLowerCase()){
                            
                            // save to cookie
                            Cookies(cookieKey, password);
                            
                            self.enterToGroupChat(groupId);
                            
                        }else{
                            
                            alertManager.hideLoading();
                            _.delay(function(){
                                alertManager.showError('invalid password');
                            }, 500) 

                        }
                        
                    },savedPassword);
                       
                }else{
                    
                    self.enterToGroupChat(groupId);
                    
                }

                
            },function(errorString){
            
                alertManager.hideLoading();
                _spikaApp.handleError(errorString,"getGroup");
            
            });
            

               
        },
        enterToGroupChat : function(groupId){
            
            var self = this;
            
            _spikaClient.loadGroupChat(groupId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                
                sideBarManager.renderGroupProfile(groupId);

                alertManager.hideLoading();
                
                self.mergeConversation(data);
                
                self.render();
                
                self.scrollToBottom();
                
                self.isLoading = false;
                
            },function(errorString){
            
                alertManager.hideLoading();
                _spikaApp.handleError(errorString,"loadGroupChat");

            
            });
            
        },
        mergeConversation : function(data){
            
            if(data.rows.length < this.chatPageRowCount){
                this.isReachedToEnd = true;
            }
            
            var oldPool = [];
            
            for(index in this.chatContentPool){
                var row = this.chatContentPool[index];
                oldPool[row._id] = row;
            }
            
            for(index in data.rows){
                
                var row = data.rows[index];

                if(_.isUndefined(row.value)){
                    continue;
                }
                
                oldPool[row.value._id] = row.value;
                
            }
            
            var tmpAry = _.sortBy(oldPool, function(message){ 
                return message.created 
            });
            
            this.chatContentPool = [];
            
            for(var index in oldPool){
                this.chatContentPool.push(oldPool[index]);
            }
            
        },
        
        render : function(){
            
            var html = '';
            
            var lastFromUserId = 0;
            var lastDateStr = '';
            var userPostsHtml = '';
            var lastRow = null;
            
            for(var index = 0 ; index < _.size(this.chatContentPool) ; index++){
                
                var row = this.chatContentPool[index];
                
                console.log(row);
                
                var date = new Date(row.created*1000);
                var dateStr = (date.getYear() + 1900)  + "." + (date.getMonth() + 1) + "." + date.getDate();
                var hour = date.getHours();
                var min = date.getMinutes();
                
                if(hour < 10)
                    hour = '0' + hour;
                    
                if(min < 10)
                    min = '0' + min;
                    
                var timeStr = hour + ":" + min;
                var fromuserId = row.from_user_id;
                
                if(lastFromUserId == 0)
                    lastFromUserId = fromuserId;
                             
                row.time = timeStr;
                row.date = dateStr;
                
                var messageType = row.message_type;
                
                if(_.isEmpty(row.avatar_thumb_file_id)){
                    row.img = this.avatarNoImage(row);
                }else{
                    row.img = this.avatarImage(row);
                }

                if(lastDateStr != dateStr || lastDateStr == ''){
                
                    if(userPostsHtml != ''){
                        userPostsHtml = this.templateUserInfo(lastRow) + userPostsHtml;
                        html += this.templateChatBlockPerson({conversation:userPostsHtml});
                        userPostsHtml = '';
                    }

                    html += this.templateDate({date:dateStr});
                    
                } else if(lastFromUserId != fromuserId){
                    userPostsHtml = this.templateUserInfo(lastRow) + userPostsHtml;
                    html += this.templateChatBlockPerson({conversation:userPostsHtml});
                    userPostsHtml = '';
                }
                
                var postHtml = '';
                
                if(messageType == 'location'){
                    postHtml = this.templateLocationPost(row);
                }else if(messageType == 'video'){
                    postHtml = this.templateVideoPost(row);
                }else if(messageType == 'voice'){
                    postHtml = this.templateVoicePost(row);
                }else if(messageType == 'emoticon'){
                    postHtml = this.templateEmoticonPost(row);
                }else if(messageType == 'image'){
                    postHtml = this.templatePicturePostMediaView(row);
                }else{
                    row.body = row.body.autoLink();
                    postHtml = this.templateTextPost(row);
                }
                
                var deleteIconHtml = '';
                
                if(row.delete_at != 0){
                    var deleteText = generateDeleteText(row.delete_at);
                    deleteIconHtml = this.templateDeleteIcon({deletetime:deleteText});
                }
                
                if(row.delete_after_shown != 0){
                    var deleteText = "After read";
                    deleteIconHtml = this.templateDeleteIcon({deletetime:deleteText});
                }
                
                var unreadIcon = '';
                
                if(row.read_at == 0 && _spikaClient.currentUser._id == row.from_user_id){
                    unreadIcon = this.templateUnreadIcon();
                }
                
                var commentsIcon = '';
                
                if(row.comment_count > 0){
                    commentsIcon = this.templateCommentsIcon(row);
                }
                                
                userPostsHtml += this.templatePostHolder({
                    content : postHtml,
                    time : timeStr,
                    user_id : row.from_user_id,
                    message_id : row._id,
                    deleteicon : deleteIconHtml,
                    unreadicon : unreadIcon,
                    commentsicon : commentsIcon
                });
                
                lastDateStr = dateStr;               
                lastRow = _.clone(row);
                lastFromUserId = fromuserId;
                
            }
            
            userPostsHtml = this.templateUserInfo(lastRow) + userPostsHtml;
            html += this.templateChatBlockPerson({conversation:userPostsHtml});

            $('#conversation_block').html(html);
            $('.deleteIcon').tooltip();

            
            var className = ".userId" + _spikaClient.currentUser._id;
            
            for(var index = 0 ; index < _.size(this.chatContentPool) ; index++){
            
                var row = this.chatContentPool[index];
                
                if(_spikaClient.currentUser._id == row.from_user_id){
                    
                    var postRowSelector = "#message" + row._id;
                    
                    $(postRowSelector).contextMenu({
                    
                        menuSelector: "#contextMenu",
                        menuSelected: function (invokedOn, selectedMenu) {
                                                        
                            var deleteMessageId = invokedOn.attr('messageid');
                            var deleteType = selectedMenu.attr('tabindex');
                            
                            if(!_.isUndefined(deleteMessageId) && 
                                !_.isEmpty(deleteType) && 
                                !_.isUndefined(deleteMessageId) && 
                                !_.isEmpty(deleteType)){
                                    
                                    _chatManager.deleteMessage(deleteMessageId,deleteType);
                                    
                            }
                            
                        },
                    }); 
                    
                    // change cursor of my messages 
                    $(postRowSelector).css('cursor','pointer');
                    
                }
                             
            }
            
   
            $(".post").hover(function(){
                $(this).css('background-color','#e5e5e5');
            },function(){
                $(this).css('background-color','#fff');
            });
   
        },
        isInConversation : function(){
        
            if(this.chatCurrentUserId != 0) {
                return true;
            } else if(this.chatCurrentGroupId != 0) {
                return true;
            } else {
                return false;
            }
            
            return false;
        },
        sendTextMessage : function(message){
            
            var self = this;
            var targetId = 0;
            var target = '';
            
            if(self.chatCurrentUserId != 0) {
                targetId = self.chatCurrentUserId;
                target = _spikaClient.MESSAGE_TAEGET_USER;
            } else if(self.chatCurrentGroupId != 0) {
                targetId = self.chatCurrentGroupId;
                target = _spikaClient.MESSAGE_TAEGET_GROUP;
            } else {
                $('#btn-chat-send').html('Send');
                $('#btn-chat-send').removeAttr('disabled');
                
                return;
            }
            
            _spikaClient.postTextMessage(target,targetId,message,function(data){
                
                $('#btn-chat-send').html('Sent');
                
                self.loadNewMessage();

                _.delay(function(){
                    $('#btn-chat-send').html('Send');
                    $('#btn-chat-send').removeAttr('disabled');
                }, 1000);
                                        
            },function(errorString){
            
                _spikaApp.handleError(errorString,"postTextMessage");
                
            });
                
        },
        sendMediaMessage : function(file,mediaType,listener){
            
            var self = this;
            var targetId = 0;
            var target = '';
            
            if(self.chatCurrentUserId != 0) {
                targetId = self.chatCurrentUserId;
                target = _spikaClient.MESSAGE_TAEGET_USER;
            } else if(self.chatCurrentGroupId != 0) {
                targetId = self.chatCurrentGroupId;
                target = _spikaClient.MESSAGE_TAEGET_GROUP;
            }else {
                $('#btn-chat-send').html('Send');
                $('#btn-chat-send').removeAttr('disabled');
                
                return;
            }
            
            if(mediaType == _spikaClient.MEDIA_TYPE_IMAGE){
                // scale
                resize(file,640,640,100,"image/jpeg",function(blobBigImage){
                    
                    resize(file,240,240,100,"image/jpeg",function(blobSmallImage){
                    
                        _spikaClient.fileUpload(blobBigImage,function(data){
                            
                            var fileId = data;
                            
                            _spikaClient.fileUpload(blobSmallImage,function(data){
                                
                                var thumbId = data;
                                
                                _spikaClient.postMediaMessage(target,mediaType,targetId,fileId,thumbId,function(data){
                                    
                                    $('#btn-chat-send').html('Sent');
                                    
                                    self.loadNewMessage();
                                    listener();
                                    
                                    _.delay(function(){
                                        $('#btn-chat-send').html('Send');
                                        $('#btn-chat-send').removeAttr('disabled');
                                    }, 1000);
                    
                                },function(errorString){
                                
                                    _spikaApp.handleError(errorString,"postMediaMessage");
                                    listener();
                                    
                                }); // post message
                                                        
                            },function(errorString){
                            
                                _spikaApp.handleError(errorString,"fileUpload");
                                listener();
                                
                            });// upload thumb
    
                                                    
                        },function(errorString){
                        
                            _spikaApp.handleError(errorString,"fileUpload");                                    
                            listener();
                            
                        });// upload image
                    
                    }); // generate thumb
                
                }); // scale image
            }
            
            if(mediaType == _spikaClient.MEDIA_TYPE_VIDEO || mediaType == _spikaClient.MEDIA_TYPE_AUDIO){
                
                _spikaClient.fileUpload(file,function(data){
                    
                    var fileId = data;
                    
                    _spikaClient.postMediaMessage(target,mediaType,targetId,fileId,0,function(data){
                        
                        $('#btn-chat-send').html('Sent');
                        
                        self.loadNewMessage();
                        listener();
                        
                        _.delay(function(){
                            $('#btn-chat-send').html('Send');
                            $('#btn-chat-send').removeAttr('disabled');
                        }, 1000);
        
                    },function(errorString){
                    
                        _spikaApp.handleError(errorString,"postMediaMessage");
                        listener();
                        
                    }); // post message
                                            
                },function(errorString){

                    _spikaApp.handleError(errorString,"fileUpload");
                    listener();
                    
                });// upload thumb

                
            }
                        

                
        },
        sendSticker : function(stickerIdentifier,listener){

            var self = this;
            var targetId = 0;
            var target = '';
            
            if(self.chatCurrentUserId != 0) {
                targetId = self.chatCurrentUserId;
                target = _spikaClient.MESSAGE_TAEGET_USER;
            } else if(self.chatCurrentGroupId != 0) {
                targetId = self.chatCurrentGroupId;
                target = _spikaClient.MESSAGE_TAEGET_GROUP;
            } else {
                $('#btn-chat-send').html('Send');
                $('#btn-chat-send').removeAttr('disabled');
                
                return;
            }
            
            _spikaClient.postStickerMessage(target,targetId,stickerIdentifier,function(data){
                
                $('#btn-chat-send').html('Sent');
                
                self.loadNewMessage();
                
                _.delay(function(){
                    $('#btn-chat-send').html('Send');
                    $('#btn-chat-send').removeAttr('disabled');
                }, 1000);
                
                listener();
                
            },function(errorString){
            
                _spikaApp.handleError(errorString,"postStickerMessage");
                listener();
                
            }); // post message
            
        },
        
        scrollToBottom : function(){
            var objConversationBlock = $('#conversation_block');
            var height = objConversationBlock[0].scrollHeight;
            objConversationBlock.scrollTop(height);
        },
        loadNewMessage : function(){

            var self = this;
            
            if(self.chatCurrentUserId != 0){
                
                this.isLoading = true;
                
                _spikaClient.loadUserChat(self.chatCurrentUserId,self.chatPageRowCount,1,function(data){
                    self.mergeConversation(data);
                    self.render();
                    self.scrollToBottom();
                },function(errorString){
                    _spikaApp.handleError(errorString,"loadUserChat");
                });
                
            } else if(self.chatCurrentGroupId != 0){
                
                this.isLoading = true;
                
                _spikaClient.loadGroupChat(self.chatCurrentGroupId,self.chatPageRowCount,1,function(data){
                    self.mergeConversation(data);
                    self.render();
                    self.scrollToBottom();
                },function(errorString){
                    _spikaApp.handleError(errorString,"loadGroupChat");
                });
            }
            
        },
        deleteMessage : function(messageId,deleteType){
            
            _spikaClient.setDelete(messageId,deleteType,function(data){
                
                console.log(_chatManager);
                
                _chatManager.resetChat();
                
            },function(errorString){
                
                _spikaApp.handleError(errorString,"setDelete");
                
            });
                
        }
            
    };
    
    // see new messages
    var newMessageChecker = {
        
        lastModified : 0,
        lastUnreadMessageCount : 0,
        checkInterval : 1000, // ms
        startUpdating : function(){
            
            var self = this;
            
            this.checkUpdate();
            
            _.delay(function(){
                self.checkUpdate();
            }, this.checkInterval);

        },
        checkUpdate : function(){
            
            var self = this;
            var isUpdateNeed = false;
            var unreadMessageCount = 0;

            _spikaClient.getActivitySummary(function(data){
                
                if(self.lastModified == 0)
                    alertManager.hideLoading();
                    
                if(data.rows[0].value.recent_activity != undefined){
                
                    if(data.rows[0].value.recent_activity.direct_messages != undefined){
                    
                        var directMessages = data.rows[0].value.recent_activity.direct_messages.notifications;      
                                          
                        for(index in directMessages){
                        
                            directMessageRow = directMessages[index];
                            timestamp = directMessageRow.messages[0]['modified'];
                            
                            if(timestamp > self.lastModified){
                                isUpdateNeed = true;
                                self.lastModified = timestamp;
                                
                                if(_chatManager.chatCurrentUserId == directMessageRow.messages[0]['from_user_id']){
                                     _chatManager.loadNewMessage();
                                }
                                   
                            }
                            
                            unreadMessageCount+=parseInt(directMessageRow.count);
                        }
                        
                    }
                    
                    if(data.rows[0].value.recent_activity.group_posts != undefined){
                    
                        var groupMessages = data.rows[0].value.recent_activity.group_posts.notifications;
                        
                        for(index in groupMessages){
                        
                            groupMessagesRow = groupMessages[index];
                            timestamp = groupMessagesRow.messages[0]['modified'];
                            
                            if(timestamp > self.lastModified){
                                isUpdateNeed = true;
                                self.lastModified = timestamp;
                                
                                if(_chatManager.chatCurrentGroupId == groupMessagesRow['target_id'])
                                    _chatManager.loadNewMessage();
                            }
                            
                            unreadMessageCount+=parseInt(groupMessagesRow.count);
                            
                        }    
                           
                    }
                }
                
                if(self.unreadMessageCount != unreadMessageCount)
                    isUpdateNeed = true;
                    
                self.unreadMessageCount = unreadMessageCount;
                
                if(isUpdateNeed){
                    navigationBarManager.renderRecentActivity();
                }
                
                _.delay(function(){
                    self.checkUpdate();
                }, self.checkInterval);
                
                if(self.unreadMessageCount > 0)
                    document.title = _lang.clientSiteTitle + ' (' + self.unreadMessageCount + ')';
                else
                    document.title = _lang.clientSiteTitle;
                
            },function(errorMessage){
                
                alertManager.hideLoading();
                _spikaApp.handleError(errorMessage,"getActivitySummary");

                
            });
            
        }
          
    };
    
    // render side bar
    var sideBarManager = {

        templateUserProflie : _.template('<div class="panel panel-primary profile-panel"><div class="panel-heading"> Profile </div><div class="panel-body"><div class="person_detail"><span id="profile-picture"><%= img %></span><br /><span id="profile-name"><%= name %></span><br /><a href="' + _consts.RootURL + '/admin/user/view/<%= _id %>">See Profile</a></div><div id="profile-description"><%= about %></div></div><div class="panel-footer"></div></div>'),
        templateGroupProflie : _.template('<div class="panel panel-primary profile-panel"><div class="panel-heading"> Profile </div><div class="panel-body"><div class="person_detail"><span id="profile-picture"><%= img %></span><br /><span id="profile-name"><%= name %></span><br /><a href="' + _consts.RootURL + '/admin/group/view/<%= _id %>">See Profile</a></div><div id="profile-description"><%= description %></div></div><div class="panel-footer"></div></div>'),
        avatarImage : _.template('<img src="' + _consts.RootURL + '/api/filedownloader?file=<%= avatar_thumb_file_id %>" alt="" width="240" height="240" class="person_img img-thumbnail" />'),
        avatarNoImage : _.template('<img src="http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture" alt="" width="240" height="240" class="person_img img-thumbnail" />'),

        renderUserProfile : function(userId){
            
            var self = this;
            
            _spikaClient.getUser(userId,function(data){

                var html = '';
                 
                if(_.isEmpty(data.avatar_file_id)){
                    data.img = self.avatarNoImage(data);
                }else{
                    data.img = self.avatarImage(data);
                }

                html += self.templateUserProflie(data);
                
                $('#sidebar_block').html(html);
                
            },function(errorString){
                
                alertManager.hideLoading();
                _spikaApp.handleError(errorString,"getUser");
                
            });

            
        },
        renderGroupProfile : function(groupId){
            
            var self = this;
            
            _spikaClient.getGroup(groupId,function(data){

                var html = '';
                 
                if(_.isEmpty(data.avatar_file_id)){
                    data.img = self.avatarNoImage(data);
                }else{
                    data.img = self.avatarImage(data);
                }
                

                html += self.templateGroupProflie(data);
                
                $('#sidebar_block').html(html);
                
            },function(errorString){
                
                alertManager.hideLoading();
                _spikaApp.handleError(errorString,"getGroup");
                
            });

            
        }
        
        
    };
