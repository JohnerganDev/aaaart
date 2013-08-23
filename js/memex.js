/*
 * jQuery File Upload Plugin JS Example 8.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

$(function () {
    'use strict';

    // override defaults
    $.fn.carousel.defaults = {
        interval: false
        , pause: 'hover'
    }


    // re-use the memex modal by flushing data on close
    $('#memex-modal.modal').on('hidden', function () { 
        $(this).off('.modal').removeData('modal') 
    });


    $.fn.hiddenDimension = function(){
        if (arguments.length && typeof arguments[0] == 'string') {
          var dimension = arguments[0]

          if (this.is(':visible')) return this[dimension]();

          var visible_container = this.closest(':visible');

          if (!visible_container.is('body')) {
            var
              container_clone = $('<div />')
                .append(visible_container.children().clone())
                .css({
                  position: 'absolute',
                  left:'-32000px',
                  top: '-32000px',
                  width: visible_container.width(),
                  height: visible_container.height()
                })
                .appendTo(visible_container),
              element_index = $('*',visible_container).index(this),
              element_clone = $('*',container_clone).slice(element_index);
            
            element_clone.parentsUntil(':visible').show();
            var result = element_clone[dimension]();
            container_clone.remove();
            return result;
          } else {
            //TO-DO: support elements whose nearest visible ancestor is <body>
            return undefined
          }
        }
        return undefined //nothing implemented for this yet
    }

    function disableSliderLooping(slider) {
        slider.on('slid', '', function() {
          var $this = $(this);
          $this.find('.memex-controls .controls').show();
          if($('.carousel-inner .item:first').hasClass('active')) {
            $this.find('.memex-prev.controls').hide();
          } 
          if($('.carousel-inner .item:last').hasClass('active')) {
            $this.find('.memex-next.controls').hide();
          }
        });
    }

    function memexInit(url) {
        $.ajax({
            type: "POST",
            url: base_url + "memex/index.php",
            data: {action: 'update_path_and_reload', url: url}, 
            dataType: 'json',
            success: function(data){
                if (data.memex) {
                    createSlides($slider, data.memex, data.pointer);
                }
                $('#memex-carousel').carousel(data.pointer);
                $actions.html(data.actions);
                $actions.find('a.remove').click(function() {
                    clearTrail();
                    return false;
                });
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        });
    }

    function removeButton(button) {
        var url = button.find('.remove').attr("data-url");
        $.ajax({
            type: "DELETE",
            url: base_url + "memex/index.php?prune=" + url,
            dataType: 'json',
            success: function(data){
                button.remove();
                // @todo: re-add the remaining buttons to the slider
            },
            error: function(){
                alert("Sorry, that didn't work!");
            }
        }); 
    }

    // Each "slide" in the Bootstrap carousel contains several buttons.
    function createSlides(slider, buttons, pointer) {
        var slide_idx = 0;
        var $current_slide = null;
        var $current_path = null;
        var $path = slider.find('.carousel-inner');
        var carousel_width = $path.width();
        var $leftover_button = null;
        var running_width = 0;
        $.each(buttons, function(idx, button) {
            if (running_width == 0) {
                $current_slide = $('<div>').addClass('item');
                $current_path = $('<ul>').addClass('thumbnails list-inline');
                $current_slide.append($current_path);
                $path.append($current_slide);
                if ($leftover_button) {
                    $current_path.append($leftover_button);
                    $leftover_button = null;
                    if (idx==pointer+1) {
                        $current_slide.addClass('active');
                    }
                }
            }
            var $button = $(button);
            $button.find('.remove').click( function() { removeButton($button); } );
            // test width
            $current_path.append($button);
            var w = $button.hiddenDimension('outerWidth');
            running_width = running_width + w;
            if (running_width > carousel_width) {
                $button.detach();
                $leftover_button = $button;
                running_width = 0;
            } else if (idx==pointer) {
                $current_slide.addClass('active');
            }
        });
        slider.trigger('slid');
    }

    // remove everything from current trail
    function clearTrail() {
        $.ajax({
            type: "DELETE",
            url: base_url + "memex/index.php",
            dataType: 'json',
            success: function(data){
                $actions.empty();
                $slider.find('.carousel-inner').empty();
                $slider.find('.memex-controls .controls').hide();
            }
        });
    }

    var $container = $('#footer');
    var $slider = null;
    var $actions = null;
    if ($container.is(":visible")) {
        $container.addClass('row');
        var $prev = $('<button class="btn btn-default controls memex-prev" data-slide="prev" href="#memex-carousel" type="button"><span class="glyphicon glyphicon-chevron-left"></span></button>');
        var $next = $('<button class="btn btn-default controls memex-next" data-slide="next" href="#memex-carousel" type="button"><span class="glyphicon glyphicon-chevron-right"></span></button>');
        var $controls = $('<div>').addClass('memex-controls btn-group btn-group-xs');
        $actions = $('<div>').addClass('memex-actions');
        $controls.append($prev).append($next);
        $slider = $('<div>').attr("id","memex-carousel").attr("data-interval","false").addClass('carousel slide');
        var $path = $('<div>').addClass('carousel-inner');
        $slider.append($actions).append($path).append($controls);
        $container.append($slider);
        memexInit(document.URL);
        disableSliderLooping($slider);
    }    

});
