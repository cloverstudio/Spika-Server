(function() {

  var autoLink,
    __slice = [].slice;

  autoLink = function() {
    var k, linkAttributes, option, options, pattern, v;
    options = 1 <= arguments.length ? __slice.call(arguments, 0) : [];
    pattern = /(^|\s)((?:https?|ftp):\/\/[\-A-Z0-9+\u0026\u2019@#\/%?=()~_|!:,.;]*[\-A-Z0-9+\u0026@#\/%=~()_|])/gi;
    if (!(options.length > 0)) {
      return this.replace(pattern, "$1<a target=\"_blank\" href='$2'>$2</a>");
    }
    option = options[0];
    linkAttributes = ((function() {
      var _results;
      _results = [];
      for (k in option) {
        v = option[k];
        if (k !== 'callback') {
          _results.push(" " + k + "='" + v + "'");
        }
      }
      return _results;
    })()).join('');
    return this.replace(pattern, function(match, space, url) {
      var link;
      link = (typeof option.callback === "function" ? option.callback(url) : void 0) || ("<a href='" + url + "'" + linkAttributes + ">" + url + "</a>");
      return "" + space + link;
    });
  };

  String.prototype['autoLink'] = autoLink;

}).call(this);

(function ($, window) {

    $.fn.contextMenu = function (settings) {

        return this.each(function () {

            // Open context menu
            $(this).on("contextmenu", function (e) {
                $(settings.menuSelector)
                    .data("invokedOn", $(e.target))
                    .show()
                    .css({
                        position: "absolute",
                        left: getLeftLocation(e),
                        top: getTopLocation(e)
                    });

                return false;
            });

            // click handler for context menu
            $(settings.menuSelector).unbind();
            $(settings.menuSelector).click(function (e) {
                $(this).hide();

                var $invokedOn = $(this).data("invokedOn");
                var $selectedMenu = $(e.target);

                settings.menuSelected.call($(this), $invokedOn, $selectedMenu);

            });

            //make sure menu closes on any click
            $(document).click(function () {
                $(settings.menuSelector).hide();
            });
        });

        function getLeftLocation(e) {
            var mouseWidth = e.pageX;
            var pageWidth = $(window).width();
            var menuWidth = $(settings.menuSelector).width();
            
            // opening menu would pass the side of the page
            if (mouseWidth + menuWidth > pageWidth &&
                menuWidth < mouseWidth) {
                return mouseWidth - menuWidth;
            } 
            return mouseWidth;
        }        
        
        function getTopLocation(e) {
            var mouseHeight = e.pageY;
            var pageHeight = $(window).height();
            var menuHeight = $(settings.menuSelector).height();

            // opening menu would pass the bottom of the page
            if (mouseHeight + menuHeight > pageHeight &&
                menuHeight < mouseHeight) {
                return mouseHeight - menuHeight;
            } 
            return mouseHeight;
        }

    };
})(jQuery, window);

function generateCommentTimeStr(createdAt){
    
    createdAt = parseInt(createdAt);
    var createdDate = new Date(createdAt * 1000);
    
    
    var str = "";
    
    str += (createdDate.getYear() + 1900) + ".";
    str += (createdDate.getMonth() + 1) + ".";
    str += createdDate.getDate() + " ";
    str += createdDate.getHours() + ".";
    str += createdDate.getMinutes();
    
    return str;
}

function generateDeleteText(deleteAt){
    
    deleteAt = parseInt(deleteAt);
    var now = parseInt(new Date().getTime() / 1000);
    var differenceInSec = deleteAt - now;
    
    var minutes = parseInt(differenceInSec / 60);
    
    if(minutes > 60)
        minutes = parseInt(differenceInSec % 60);
        
    var hours = parseInt(differenceInSec / 60 / 60);
    if(hours > 24)
        hours = parseInt(hours % 24);
    
    var days = parseInt(differenceInSec / 60 / 60 / 24);
    
    var deleteText = "";

    if(minutes > 0)
        deleteText = "in " + minutes + " minutes";
    
    if(hours > 0)
        deleteText = "in " + hours + " hours";

    if(days > 0)
        deleteText = "in " + days + " days";
    
    
    return deleteText;
}

function dataURItoBlob(dataURI) {
  // convert base64 to raw binary data held in a string
  // doesn't handle URLEncoded DataURIs - see SO answer #6850276 for code that does this
  var byteString = atob(dataURI.split(',')[1]);

  // separate out the mime component
  var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0]

  // write the bytes of the string to an ArrayBuffer
  var ab = new ArrayBuffer(byteString.length);
  var ia = new Uint8Array(ab);
  for (var i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
  }

  var blob = new Blob([ab], {type: mimeString}); 
  return blob;

}

function resize(file, max_width, max_height, compression_ratio, imageEncoding, listener){

    var fileLoader = new FileReader(),
    canvas = document.createElement('canvas'),
    context = null,
    imageObj = new Image(),
    blob = null;            

    //create a hidden canvas object we can use to create the new resized image data
    canvas.id     = "hiddenCanvas";
    canvas.width  = max_width;
    canvas.height = max_height;
    canvas.style.visibility   = "hidden";   
    document.body.appendChild(canvas);  

    //get the context to use 
    context = canvas.getContext('2d');  

    // check for an image then
    //trigger the file loader to get the data from the image         
    if (file.type.match('image.*')) {
        fileLoader.readAsDataURL(file);
    } else {
        alert('File is not an image');
    }

    // setup the file loader onload function
    // once the file loader has the data it passes it to the 
    // image object which, once the image has loaded, 
    // triggers the images onload function
    fileLoader.onload = function() {
        var data = this.result; 
        imageObj.src = data;
    };

    fileLoader.onabort = function() {
        alert("The upload was aborted.");
    };

    fileLoader.onerror = function() {
        alert("An error occured while reading the file.");
    };  

    // set up the images onload function which clears the hidden canvas context, 
    // draws the new image then gets the blob data from it
    imageObj.onload = function() {  
        
        var self = this;
        
        var left = 0;
        var top = 0;
        var size = 0;
        
        if(this.height > this.width){            
            left = 0;
            top = (this.height - this.width) / 2;
            size = this.width;
        }else{
            left = (this.width - this.height) / 2;
            top = 0;
            size = this.height;
        }
        
        // Check for empty images
        if(this.width == 0 || this.height == 0){
            alert('Image is empty');
        } else {                

            context.clearRect(0,0,max_width,max_height);
            context.drawImage(imageObj, left, top, size, size, 0, 0, max_width, max_height);

            blob = dataURItoBlob(canvas.toDataURL(imageEncoding));

            listener(blob);
        }       
    };

    imageObj.onabort = function() {
        alert("Image load was aborted.");
    };

    imageObj.onerror = function() {
        alert("An error occured while loading image.");
    };

}