(function() {
    
    // handles window ( mainly size change )
    var windowManager = {
        
        init : function(window){
            
            var self = this;
            $(window).resize(function() {
                self.onResize();
            });
            
            this.onResize();
            
        },
        onResize : function(){
            var headerHeight = $('.navbar-static-top').height();
            var chatboxHeight =  $('#chat_block').height();

            $('body').height(window.innerHeight);
            $('.sidebar-collapse .tab-content').height(window.innerHeight - headerHeight - 50);
            $('#conversation_block').height(window.innerHeight - chatboxHeight - headerHeight - 50);
        }  
        
    };
    
    // handles modal dialogs
    var alertManager = {
        
        showAlert : function(title,message,buttonText,onClose){
            
            $('#modalAlertDialog #modalTitle').text(title);
            $('#modalAlertDialog #modalText').text(message);
            $('#modalAlertDialog #modalDismissButton').text(buttonText);
            
            $('#modalAlertDialog').modal('show');
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
        }
            
    };
    
    // navigation bar renderer
    var navigationBarManager = {
        
        userList : {},
        groupList : {},
        unreadMessageNumPerUser : {},
        unreadMessageNumPerGroup : {},        
        templateUserRow : _.template('<li><a href="javascript:_chatManager.startPrivateChat(<%= _id %>)"><%= img %><%= name %></a></li>'),
        templateGroupsRow : _.template('<li><a href="javascript:_chatManager.startGroupChat(<%= _id %>)"><%= img %><%= name %></a></li>'),
        avatarImage : _.template('<img src="' + _consts.RootURL + '/api/filedownloader?file=<%= avatar_thumb_file_id %>" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        avatarNoImage : _.template('<img src="http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        templateRecentActivityRowUser : _.template('<li><a href="javascript:_chatManager.startPrivateChat(<%= userId %>)"><%= img %><i class="fa fa-user"></i> <%= name %> <%= count %></a></li>'),
        templateRecentActivityRowGroup : _.template('<li><a href="javascript:_chatManager.startGroupChat(<%= groupId %>)"><%= img %><i class="fa fa-users"></i> <%= name %> <%= count %></a></li>'),
        
        renderContacts : function(userList){
            
            var self = this;
            _spikaClient.getContacts(function(users){
                
                var html = '';
                _.each(users, function(data){
                    
                    if(_.isEmpty(data.avatar_thumb_file_id)){
                        data.img = self.avatarNoImage(data);
                    }else{
                        data.img = self.avatarImage(data);
                    }
                    
                    html += self.templateUserRow(data);
                    
                });
                
                $('#tab-users ul').html(html);
                
                
            },function(errorMessage){
            
                alertManager.showError(_lang.messageGeneralError);
                
            });
            
        },
        renderGroups : function(userList){
        
            var self = this;
            _spikaClient.getFavoriteGroups(function(groups){
                
                var html = '';
                _.each(groups, function(data){

                    if(_.isEmpty(data.avatar_thumb_file_id)){
                        data.img = self.avatarNoImage(data);
                    }else{
                        data.img = self.avatarImage(data);
                    }
                    
                    html += self.templateGroupsRow(data);
                    
                });
                
                $('#tab-groups ul').html(html);
                
            },function(errorMessage){
            
                alertManager.showError(_lang.messageGeneralError);
                
            });
            
        },
        renderRecentActivity : function(userList){
        
            var self = this;
            
            this.userList = {};
            this.groupList = {};
            this.unreadMessageNumPerUser = {};
            this.unreadMessageNumPerGroup = {};   
            
            _spikaClient.getActivitySummary(function(data){

                var html = '';
                var totalUnreadMessage = 0;
                
                var usersId = new Array();
                var groupsId = new Array();
                
                if(data.rows[0].value.recent_activity != undefined){
                
                    if(data.rows[0].value.recent_activity.direct_messages != undefined){
                    
                        var directMessages = data.rows[0].value.recent_activity.direct_messages.notifications;
                        
                        for(index in directMessages){
                            var directMessageRow = directMessages[index];
                            var fromUserId = directMessageRow.messages[0]['from_user_id'];
                            var timestamp = directMessageRow.messages[0]['modified'];
                            var key = timestamp + + fromUserId;
                            
                            usersId.push(fromUserId);
                            
                            if(_.isUndefined(self.unreadMessageNumPerUser[key])){
                                self.unreadMessageNumPerUser[key] = {count:0,userId:fromUserId};
                            }
                            
                            self.unreadMessageNumPerUser[key].count += parseInt(directMessageRow.count);
                            
                        }
                        
                    }
                    
                    if(data.rows[0].value.recent_activity.group_posts != undefined){
                    
                        var groupMessages = data.rows[0].value.recent_activity.group_posts.notifications;

                        for(index in groupMessages){
                            
                            var groupMessageRow = groupMessages[index];
                            var groupId = groupMessageRow['target_id'];
                            var timestamp = groupMessageRow.messages[0]['modified'];
                            var key = groupId;
                            
                            groupsId.push(groupId);
                            
                            if(_.isUndefined(self.unreadMessageNumPerGroup[key])){
                                self.unreadMessageNumPerGroup[key] = {count:0,groupId:groupId};
                            }
                            
                            self.unreadMessageNumPerGroup[key].count += parseInt(groupMessageRow.count);
                            
                        }    
                           
                    }
                }                
                
                usersId = _.uniq(usersId);
                groupsId = _.uniq(groupsId);
                
                _spikaClient.getUser(usersId.join(','),function(data){
                    
                    if(usersId.length == 1){
                        data = [data];
                    }
                    
                    for(userid in data){
                        
                        self.userList[data[userid]['_id']] = data[userid];
                        
                    }
                    
                    _spikaClient.getGroup(groupsId.join(','),function(data){
                        
                        if(groupsId.length == 1){
                            data = [data];
                        }
                        
                        for(groupid in data){
                            
                            self.groupList[data[groupid]['_id']] = data[groupid];
                            
                        }
                        
                        // every information are fetched
                        self.renderRecentActivityNext();
                        
                    },function(errorString){
                        
                        alertManager.hideLoading();
                        
                    });
                
                },function(errorString){
                    
                    alertManager.hideLoading();
                    
                });
                
            },function(errorMessage){
            
                alertManager.showError(_lang.messageGeneralError);
                alertManager.hideLoading();
                
            });
            
        },
        renderRecentActivityNext : function(){
            
            if(_.isEmpty(this.userList)){
                return;
            }
            
            if(_.isEmpty(this.groupList)){
                return;
            }
            
            if(_.isEmpty(this.unreadMessageNumPerUser)){
                return;
            }
            
            if(_.isEmpty(this.unreadMessageNumPerGroup)){
                return;
            }
            
            var html = '';
            
            var keys = _.keys(this.unreadMessageNumPerUser);
            keys = keys.reverse();
            for(var i = 0 ; i < keys.length ; i++){
                
                var key = keys[i];
                var userId = this.unreadMessageNumPerUser[key].userId;
                var count = this.unreadMessageNumPerUser[key].count;
                var data = this.userList[userId];

                if(count > 0){
                    data.count = '(' + count + ')';
                }else{
                    data.count = '';
                }
                 
                if(_.isEmpty(data.avatar_thumb_file_id)){
                    data.img = this.avatarNoImage(data);
                }else{
                    data.img = this.avatarImage(data);
                }
                
                data.userId = userId;
                
                html += this.templateRecentActivityRowUser(data);
                
            }
            
            var keys = _.keys(this.unreadMessageNumPerGroup);
            keys = keys.reverse();
            for(var i = 0 ; i < keys.length ; i++){
                
                var key = keys[i];
                var groupId = this.unreadMessageNumPerGroup[key].groupId;
                var count = this.unreadMessageNumPerGroup[key].count;
                var data = this.groupList[groupId];
                
                if(count > 0){
                    data.count = '(' + count + ')';
                }else{
                    data.count = '';
                }
                 
                if(_.isEmpty(data.avatar_thumb_file_id)){
                    data.img = this.avatarNoImage(data);
                }else{
                    data.img = this.avatarImage(data);
                }
                
                data.groupId = groupId;

                html += this.templateRecentActivityRowGroup(data);
                
                alertManager.hideLoading();
                
            }
            
            $('#tab-recent ul').html(html);
            
        }
        
    }
    
    // everything for caht
    _chatManager = {
        
        templateDate : _.template('<div class="timestamp_date"><p><%= date %></p></div>'),
        templateChatBlockPerson : _.template('<div class="post_block"><%= conversation %></div>'),
        templateUserInfo : _.template('<div class="person_info"><h5><%= img %><a target="_blank" href="' + _consts.RootURL + '/admin/user/view/<%= from_user_id %>"><%= from_user_name %></a></h5><div class="clear"></div></div>'),
        avatarImage : _.template('<img src="' + _consts.RootURL + '/api/filedownloader?file=<%= avatar_thumb_file_id %>" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        avatarNoImage : _.template('<img src="http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        templateTextPost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="post_content"><%= body %></div></div>'),
        templatePicturePost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="post_content"><a class="img-thumbnail" data-toggle="modal" data-target=".bs-example-modal-lg<%= _id  %>"><img src="' + _consts.RootURL + '/api/filedownloader?file=<%= picture_thumb_file_id %>" height="120" width="120" /></a></div></div><div class="modal fade bs-example-modal-lg<%= _id %>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><img src="' + _consts.RootURL + '/api/filedownloader?file=<%= picture_file_id %>" /></div></div></div>'),
        templateEmoticonPost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="post_content"><img src="<%= emoticon_image_url %>" height="120" width="120" /></div></div>'),
        templateVoicePost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="fa fa-play-circle-o fa-4x post_fa"></div><div class="post_content post_media"><%= body %><br /><audio controls><source src="' + _consts.RootURL + '/api/filedownloader?file=<%= voice_file_id %>" width="50" type="audio/wav"><a target="_blank" href="' + _consts.RootURL + '/api/filedownloader?file=<%= voice_file_id %>">' + _lang.listenVoice + '</a></audio></div></div>'),
        templateVideoPost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="fa fa-video-camera fa-4x post_fa"></div><div class="post_content post_media"><%= body %><br /><a target="_blank" href="' + _consts.RootURL + '/api/filedownloader?file=<%= video_file_id %>">' + _lang.watchVideo + '</a></div></div>'),
        templateLocationPost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="fa fa-location-arrow fa-4x post_fa"></div><div class="post_content post_media"><%= body %><br /><a target="_blank" href="http://maps.google.com/?q=<%= latitude %>,<%= longitude %>">' + _lang.openInGoogleMap + '</a></div></div>'),
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
    
                });
                
            }
            
        },
        startPrivateChat : function(userId){
            
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
                
                alertManager.showError(_lang.messageGeneralError);
            
            });
            
        },
        
        startGroupChat : function(groupId){
            
            var self = this;
            
            alertManager.showLoading();
            
            this.chatCurrentGroupId = groupId;
            this.chatCurrentUserId = 0;
            this.chatCurrentPage = 1;
            this.chatContentPool = [];
            
            self.isLoading = true;
                
            _spikaClient.loadGroupChat(groupId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                
                sideBarManager.renderGroupProfile(groupId);

                alertManager.hideLoading();
                
                self.mergeConversation(data);
                
                self.render();
                
                self.scrollToBottom();
                
                self.isLoading = false;
                
            },function(errorString){
            
                alertManager.hideLoading();
                
                alertManager.showError(_lang.messageGeneralError);
            
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

                if(messageType == 'location'){
                    userPostsHtml += this.templateLocationPost(row);
                }else if(messageType == 'video'){
                    userPostsHtml += this.templateVideoPost(row);
                }else if(messageType == 'voice'){
                    userPostsHtml += this.templateVoicePost(row);
                }else if(messageType == 'emoticon'){
                    userPostsHtml += this.templateEmoticonPost(row);
                }else if(messageType == 'image'){
                    userPostsHtml += this.templatePicturePost(row);
                }else{
                    row.body = row.body.autoLink();
                    userPostsHtml += this.templateTextPost(row);
                }
                 
                lastDateStr = dateStr;               
                lastRow = _.clone(row);
                lastFromUserId = fromuserId;

            }
            
            userPostsHtml = this.templateUserInfo(lastRow) + userPostsHtml;
            html += this.templateChatBlockPerson({conversation:userPostsHtml});

            $('#conversation_block').html(html);
                        
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
            }
            
            _spikaClient.postTextMessage(target,targetId,message,function(data){
                
                $('#btn-chat-send').html('Sent');
                
                self.loadNewMessage();

                _.delay(function(){
                    $('#btn-chat-send').html('Send');
                    $('#btn-chat-send').removeAttr('disabled');
                }, 1000);
                                        
            },function(errorString){
            
                alertManager.showError(_lang.messageGeneralError);
                
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
            }
            
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
                                
                            },function(errorString){
                            
                                alertManager.showError(_lang.messageGeneralError);
                                listener();
                                
                            }); // post message
                                                    
                        },function(errorString){
                        
                            alertManager.showError(_lang.messageGeneralError);
                            listener();
                            
                        });// upload thumb

                                                
                    },function(errorString){
                    
                        alertManager.showError(_lang.messageGeneralError);
                        listener();
                        
                    });// upload image
                
                }); // generate thumb
            
            }); // scale image
            

                
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
    
                });
                
            } else if(self.chatCurrentGroupId != 0){
                
                this.isLoading = true;
                
                _spikaClient.loadGroupChat(self.chatCurrentGroupId,self.chatPageRowCount,1,function(data){
                    self.mergeConversation(data);
                    self.render();
                    self.scrollToBottom();
                },function(errorString){
    
                });
            }
            
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
                
            });
            
        }
          
    };
    
    // render side bar
    var sideBarManager = {

        templateUserProflie : _.template('<div class="panel panel-primary"><div class="panel-heading"> Profile </div><div class="panel-body"><div class="person_detail"><span id="profile-picture"><%= img %></span><br /><span id="profile-name"><%= name %></span><br /><a href="' + _consts.RootURL + '/admin/user/view/<%= _id %>">See Profile</a></div><div id="profile-description"><%= about %></div></div><div class="panel-footer"></div></div>'),
        templateGroupProflie : _.template('<div class="panel panel-primary"><div class="panel-heading"> Profile </div><div class="panel-body"><div class="person_detail"><span id="profile-picture"><%= img %></span><br /><span id="profile-name"><%= name %></span><br /><a href="' + _consts.RootURL + '/admin/group/view/<%= _id %>">See Profile</a></div><div id="profile-description"><%= description %></div></div><div class="panel-footer"></div></div>'),
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
                
            });

            
        }
        
        
    };
    
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
            
            if(fileType != 'image/jpeg' && fileType != 'mp4'){
                alertManager.showError(_lang.messageValidationErrorWrongFileType);
                return;
            }
            
            // upload
            $('#fileupload-box').css('display','none');
            $('#fileuploading').css('display','block');
            
            _chatManager.sendMediaMessage(file,_spikaClient.MEDIA_TYPE_IMAGE,function(){
                $('#fileupload-box').css('display','block');
                $('#fileuploading').css('display','none');
            });
            
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

        },function(errorString){
        
            alertManager.showAlert(_lang.labelErrorDialogTitle,_lang.messageTokenError,_lang.labelCloseButton,function(){
                location.href = "login";
            });
            
        });
        
        $('#btn-chat-send').click(function(){
            
            _chatManager.sendTextMessage($('#textarea').val());
            $('#textarea').val('');
            $('#btn-chat-send').html('<i class="fa fa-refresh fa-spin"></i> Sending');
            $('#btn-chat-send').attr('disabled','disabled');
            
        });
        
        $('#btn_text').click(function(){
            console.log(1);
            $('#textarea').css('display','block');
            $('#sticker').css('display','none');
            $('#fileupload').css('display','none');
             
        });
        $('#btn_sticker').click(function(){
            console.log(2);
            $('#textarea').css('display','none');
            $('#sticker').css('display','block');
            $('#fileupload').css('display','none');
             
        });
        $('#btn_file').click(function(){
            console.log(3);
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
