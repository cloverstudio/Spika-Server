(function() {
    
    // window resize handling
    
    window.onload = function() {
    
        _.extend(window, Backbone.Events);
    
        window.onresize = function() { window.trigger('resize') };
    
        ViewDirect = Backbone.View.extend({
    
            initialize: function() {
                this.listenTo(window, 'resize', _.debounce(this.print));
            },
        
            print: function() {
                console.log('Window width, heigth: %s, %s',
                window.innerWidth,
                window.innerHeight);
                
                $('#right-nav').height(window.innerHeight - 100);
            },
    
        });
    
        var myview = new ViewDirect();
        
     }
    
})();