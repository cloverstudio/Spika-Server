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
            var headerHeight = $('.navbar-static-top').outerHeight();
            var chatboxHeight =  $('#chat_block').outerHeight();
            var submenuHeight =  $('#submenu').outerHeight();
            
            $('body').height(window.innerHeight);
            $('#main-view').height(window.innerHeight - headerHeight - submenuHeight);
            $('#media-view').height(window.innerHeight - headerHeight - submenuHeight);
            $('.sidebar-collapse .tab-content').height(window.innerHeight - headerHeight);
            $('#conversation_block').height(window.innerHeight - headerHeight - chatboxHeight - submenuHeight - 20);
            
        }  
        
    };
