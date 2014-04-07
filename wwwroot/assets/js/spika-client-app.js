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
            $('#conversation_block').height(window.innerHeight - chatboxHeight - headerHeight - 10);
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

                        for(userid in data){
                            
                            self.userList[data[userid]['_id']] = data[userid];
                            
                        }
                    

                    _spikaClient.getGroup(groupsId.join(','),function(data){
                        
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
        templateUserInfo : _.template('<div class="person_info"><h5><%= img %><%= from_user_name %></h5><div class="clear"></div></div>'),
        avatarImage : _.template('<img src="' + _consts.RootURL + '/api/filedownloader?file=<%= avatar_thumb_file_id %>" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        avatarNoImage : _.template('<img src="http://dummyimage.com/60x60/e2e2e2/7a7a7a&text=nopicture" alt="" width="40" height="40" class="person_img img-thumbnail" />'),
        templateOnePost : _.template('<div class="post"><div class="timestamp"><%= time %></div><div class="post_content"><%= body %></div></div>'),
        chatPageRowCount : 30,
        chatCurrentPage : 0,
        chatCurrentUserId : 0,
        chatCurrentGroupId : 0,
        chatContentPool : {},

        startPrivateChat : function(userId){
            
            var self = this;
            
            alertManager.showLoading();
            
            this.chatCurrentUserId = userId;
            this.chatCurrentGroupId = 0;
            
            _spikaClient.loadUserChat(userId,this.chatPageRowCount,this.chatCurrentPage,function(data){
                
                sideBarManager.renderUserProfile(userId);
                
                alertManager.hideLoading();
                
                self.chatContentPool = {};
                
                self.mergeConverSation(data);
            
                self.render();
                
                self.scrollToBottom();
                
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
            
            _spikaClient.loadGroupChat(groupId,this.chatPageRowCount,this.chatCurrentPage,function(data){
            
                self.chatContentPool = {};
                
                self.mergeConverSation(data);
            
                self.render();
                
                self.scrollToBottom();
                
            },function(errorString){
            
                alertManager.hideLoading();
                
                alertManager.showError(_lang.messageGeneralError);
            
            });
               
        },
        mergeConverSation : function(data){
            
            data.rows.reverse();
            
            for(index in data.rows){
                
                var row = data.rows[index];
                
                if(_.isUndefined(row.value)){
                    return;
                }
                
                var value = row.value;
                
                if(_.isUndefined(value.created) || _.isUndefined(value.from_user_id) || _.isUndefined(value.body)){
                    return;
                }
                
                var date = new Date(value.created*1000);
                var dateStr = (date.getYear() + 1900)  + "." + (date.getMonth() + 1) + "." + date.getDate();
                var timeStr = date.getHours() + ":" + date.getMinutes() + _.random(0, 100);
                var fromuserId = value.from_user_id;
                
                if(_.isUndefined(this.chatContentPool[dateStr]))
                    this.chatContentPool[dateStr] = {};
                
                
                var lastValue = "";
                var lastKey = "";
                
                for(var lastKey in this.chatContentPool[dateStr]){
                    if(this.chatContentPool[dateStr][lastKey].length > 0){
                        lastValue = this.chatContentPool[dateStr][lastKey][0];
                    }
                }
                
             
                if(_.isEmpty(lastValue)){
                    
                    var messages = new Array();
                    messages.push(value);
                  
                    this.chatContentPool[dateStr][timeStr] = messages;
                    
                    
                }else{

                    if(lastValue.from_user_id == fromuserId){
                        
                        var currentMessages = this.chatContentPool[dateStr][lastKey];
                        if(_.isUndefined(currentMessages))
                            currentMessages = new Array();
                            
                        currentMessages.push(value);
                        
                        this.chatContentPool[dateStr][lastKey] = currentMessages;
                        console.log("2" + value._id);

                    }else{
                        
                        var messages = new Array();
                        messages.push(value);
                      
                        this.chatContentPool[dateStr][timeStr] = messages;


                    }
                 
                }

            }
            
            
        },
        
        render : function(){
            
            var html = '';

            for(var date in this.chatContentPool){
                
                html += this.templateDate({date:date});
                
                
                for(var tmp in this.chatContentPool[date]){
                    
                    var postsHtml = "";
                     
                    var firstRow = this.chatContentPool[date][tmp][0];
                    
                    if(_.isEmpty(firstRow.avatar_thumb_file_id)){
                        firstRow.img = this.avatarNoImage(firstRow);
                    }else{
                        firstRow.img = this.avatarImage(firstRow);
                    }
                    
                    postsHtml += this.templateUserInfo(firstRow);
                   
                    for(var tmp2 in this.chatContentPool[date][tmp]){
                        
                        var post = this.chatContentPool[date][tmp][tmp2];
                        
                        var dateObj = new Date(post.created*1000);
                        var hour = dateObj.getHours();
                        var min = dateObj.getMinutes();
                        
                        if(hour < 10)
                            hour = '0' + hour;
                            
                        if(min < 10)
                            min = '0' + min;
                            
                        var timeStr = hour + ":" + min;
                        
                        post.time = timeStr;
                        postsHtml += this.templateOnePost(post);
                        
                    }
                    
                    html += this.templateChatBlockPerson({conversation:postsHtml});
                    
                }


                
            }
            
            
            $('#conversation_block').html(html);
            
        },
        sendTextMessage : function(message){
            
            var self = this;
            
            if(self.chatCurrentUserId != 0) {
            
                _spikaClient.postTextMessageToUser(self.chatCurrentUserId,message,function(data){
                    
                    $('#btn-chat-send').html('Sent');
                    
    
                    _spikaClient.loadUserChat(self.chatCurrentUserId,self.chatPageRowCount,self.chatCurrentPage,function(data){
                        
                        setTimeout(function(){
                            $('#btn-chat-send').html('Send');
                            $('#btn-chat-send').removeAttr('disabled');
                        }, 1000);
                    
                        self.chatContentPool = {};
                        
                        self.mergeConverSation(data);
                    
                        self.render();
                        
                        self.scrollToBottom();
                        
                    },function(errorString){
                    
                        alertManager.showError(_lang.messageGeneralError);
                    
                        setTimeout(function(){
                            $('#btn-chat-send').html('Send');
                            $('#btn-chat-send').removeAttr('disabled');
                        }, 1000);
                        
                    });
                    
                },function(errorString){
                
                    alertManager.showError(_lang.messageGeneralError);
                    
                });
            
            } else if(self.chatCurrentGroupId != 0) {
                
                _spikaClient.postTextMessageToGroup(self.chatCurrentGroupId,message,function(data){
                    
                    $('#btn-chat-send').html('Sent');
                    
    
                    _spikaClient.loadGroupChat(self.chatCurrentGroupId,self.chatPageRowCount,self.chatCurrentPage,function(data){
                    
                        setTimeout(function(){
                            $('#btn-chat-send').html('Send');
                            $('#btn-chat-send').removeAttr('disabled');
                        }, 1000);
                    
                        self.chatContentPool = {};
                        
                        self.mergeConverSation(data);
                    
                        self.render();
                        
                        self.scrollToBottom();
                        
                    },function(errorString){
                    
                        alertManager.showError(_lang.messageGeneralError);
                    
                        setTimeout(function(){
                            $('#btn-chat-send').html('Send');
                            $('#btn-chat-send').removeAttr('disabled');
                        }, 1000);
                        
                    });
                    
                },function(errorString){
                
                    alertManager.showError(_lang.messageGeneralError);
                    
                });

                
            }
            
        },
        scrollToBottom : function(){
            var objConversationBlock = $('#conversation_block');
            var height = objConversationBlock[0].scrollHeight;
            objConversationBlock.scrollTop(height);
        }
            
    };
    
    // see new messages
    var newMessageChecker = {
        
        lastModified : 0,
        checkInterval : 1000, // ms
        startUpdating : function(){
            
            var self = this;
            
            this.checkUpdate();
            
            setTimeout(function(){
                self.checkUpdate();
            }, this.checkInterval);
            
        },
        checkUpdate : function(){
            
            var self = this;
            var isUpdateNeed = false;
            
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
                            }
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
                            }
                            
                        }    
                           
                    }
                }
                
                if(isUpdateNeed){
                    navigationBarManager.renderRecentActivity();
                }
                
                setTimeout(function(){
                    self.checkUpdate();
                }, self.checkInterval);
            
            },function(errorMessage){
            
                alertManager.hideLoading();
                
            });
            
        }
          
    };
    
    // render side bar
    var sideBarManager = {

        templateProflie : _.template('<div class="panel panel-primary"><div class="panel-heading"> Profile </div><div class="panel-body"><div class="person_detail"><span id="profile-picture"><%= img %></span><br /><span id="profile-name"><%= name %></span><br /><a href="' + _consts.RootURL + '/admin/user/view/<%= _id %>">See Profile</a></div><div id="profile-description"><%= about %></div></div><div class="panel-footer"></div></div>'),
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

                html += self.templateProflie(data);
                
                $('#sidebar_block').html(html);
                
            },function(errorString){
                
                alertManager.hideLoading();
                
            });

            
        }
        
    };
    
    $(document).ready(function() {
    
        alertManager.showLoading();
        
        windowManager.init(window);
        
        // login
        _spikaClient.login(_loginedUser.email,_loginedUser.password,function(data){
            
            _loginedUser = data;
            _spikaClient.setCurrentUser(_loginedUser);
            
            navigationBarManager.renderContacts();
            navigationBarManager.renderGroups();
            
            newMessageChecker.startUpdating();
            
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
        
    });

})();