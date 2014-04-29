
    
    var stickerViewManager = {
        templateSticker : _.template('<li class="sticker-view" stickerId="<%= identifier %>"><img src="<%= stickerUrl %>" alt="" width="120" height="120" /></li>'),    
        sending : false,
        isLoaded : false,
        render : function(){
            
            var self = this;
            
            if(this.isLoaded)
                return;
                
            _spikaClient.loadStickers(function(data){

                if(!_.isArray(data.rows))
                    return;
                
                var html = '';
                
                _.each(data.rows,function(row,key,list){
                    
                    var value = row.value;
                    
                    if(_.isUndefined(value))
                        return;
                    
                    value.stickerUrl =  _consts.RootURL + "/api/Emoticon/" + value._id;
                    html += self.templateSticker(value);
                          
                });
                
                $('#sticker-holder').html(html);
                
                $('#sticker-holder').css('width',130 * data.rows.length);
                
                $('.sticker-view').click(function(){

                    if(!_chatManager.isInConversation()){
                        return;
                    }
            
                    var stickerIdentifier = $(this).attr('stickerId');
                    
                    if(!_.isUndefined(stickerIdentifier)){
                        
                        if(self.sending == true)
                            return;
                            
                        self.sending = true;
                        $('.sticker-view').css('cursor','progress');
                        
                        _chatManager.sendSticker(stickerIdentifier,function(){
                            self.sending = false;
                            $('.sticker-view').css('cursor','pointer');
                        });

                        $('#btn-chat-send').html('<i class="fa fa-refresh fa-spin"></i> Sending');
                        $('#btn-chat-send').attr('disabled','disabled');
                        
                    }
                    
                });
                     
                this.isLoaded = true;          

            },function(errorString){
                
                _spikaApp.handleError(errorString,"loadStickers");
            });
            
        }
    };