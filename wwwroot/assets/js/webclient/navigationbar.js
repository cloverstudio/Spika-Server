 
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
            
                _spikaApp.handleError(errorString,"getContacts");

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
            
                _spikaApp.handleError(errorString,"getFavoriteGroups");
                
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
                
                if(usersId.length == 0){
                    //console.log('abort no user');
                    return;
                }
                    
                _spikaClient.getUser(usersId.join(','),function(data){
                    
                    if(usersId.length == 1){
                        data = [data];
                    }
                    
                    for(userid in data){
                        
                        self.userList[data[userid]['_id']] = data[userid];
                        
                    }
                    
                    if(groupsId.length == 0){
                        //console.log('abort no group');
                        self.groupList = [];
                        self.renderRecentActivityNext();
                        return;
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
                        _spikaApp.handleError(errorString,"getGroup");
                        
                    });
                
                },function(errorString){
                    
                    alertManager.hideLoading();
                    _spikaApp.handleError(errorString,"getUser");
                    
                });
                
            },function(errorMessage){
                
                alertManager.hideLoading();
                _spikaApp.handleError(errorMessage,"getActivitySummary");
                
            });
            
        },
        renderRecentActivityNext : function(){
            
            if(_.isEmpty(this.userList)){
                this.userList = [];
            }
            
            if(_.isEmpty(this.groupList)){
                this.groupList = [];
            }
            
            if(_.isEmpty(this.unreadMessageNumPerUser)){
                this.unreadMessageNumPerUser = [];
            }
            
            if(_.isEmpty(this.unreadMessageNumPerGroup)){
                this.unreadMessageNumPerGroup = [];
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
    
    

    
