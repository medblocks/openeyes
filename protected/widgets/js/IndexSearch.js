(function (exports) {
  'use strict';

  /* Global Variables */
  var show_children = true;
  var show_and_search_descriptions = true;
  var last_search_pos;
  var is_loading_timeout;
  /* End of Global varaibles */

  /*
  * jQuery Search Widget:
  * Generic code that shows, hides and highlights text in a HTML structure
  * that has been generated by eyedrawconfigload yii command
  */
  (function($) {
    let opts;
    let search_term;
    $.fn.search = function(options) {
      opts = $.extend({}, $.fn.search.defaults, options);
      let $results = $("#results");
      let $parent = $results.parent();
      this.keyup(function(e, i) {
        // TODO:  efficiency let visibleOrHidden = (e.keyCode == 8) ? ':hidden' : ':visible';
        //temporarily detaches div from DOM to reduce unnecessary rendering
        $results.detach();
        $results.find('#did_you_mean_suggestion').remove();
        search_term = ($(this).val() + "").toLowerCase();
        let last_level,$this,$element,highlighted_string,alias,selector,match_pos
        ,plain_text,no_match_text,match_text,$alias,highlighted_aliases;
        for (selector of opts.selectors) {
          //determines whether the index selecor is for the last level i.e  the index has no children
          last_level = opts.selectors[0] == selector;
          $results.find(selector).each(function() {
            $this = $(this);
            $element = get_element($this);
            $alias = $element.children(':first').next('.index_row').find('.alias:first');
            let $description = $element.children(':first').next('.index_row').find('.description_note:first');
            alias = $this.data("alias").toLowerCase();
            if (show_and_search_descriptions) {
              alias += $description.text().toLowerCase();
            }
            if ((match_pos = alias.indexOf(search_term)) == -1) { //no matches
              $this.html($this.text()); //removes highlighted text
              $alias.html($alias.text());
              $description.html($description.text());
              if (!last_level && $element.children().find("li[style!='display: none;']").length != 0) {
                $element.show(); //has visible children index so should be visible to maintain tree structure
              } else {
                $element.hide(); //has no visible children so hide
              }
            } else { //match found
              //clear previous highlights and slice up to the start of match
              //uses the indexOf used to check presence to slice string
              //benefitical if the alias list is very long as it means
              //length of string is likely less
              plain_text = $this.text();
              no_match_text = plain_text.slice(0,(match_pos-1) < 0 ? 0 : (match_pos)); //may over slice if in alias but does not matter
              match_text = plain_text.slice(match_pos);
              highlighted_string = no_match_text + replace_matched_string(match_text); //match_text may be empty
              $this.html(highlighted_string);
              highlighted_aliases = replace_matched_string($alias.text());
              $alias.html(highlighted_aliases);
              highlighted_aliases = replace_matched_string($description.text());
              $description.html(highlighted_aliases);

              $element.show();
              if (!last_level) { //does it have children?
                if (show_children == true) { //is the toggle show children on?
                  $element.children().find("li[style='display: none;']").show(); //show index descendents
                }
              }
            }
          });
        }
        if ($results.find("li[style!='display: none;']").length === 0){
          get_did_you_mean($results);
        }
          $parent.append($results); //reattaches the result to the DOM to be rendered so just 1 big render instead of many small renders
          add_did_you_mean_listerner($results); //can add refineent if more than one match i.e take first letter
      });
      return this;
    };
    $.fn.search.defaults = {
      selectors: [".lvl3", ".lvl2", ".lvl1"], //order of selection
      ancestor_to_change: 2, //how many times parent() need to be used to get from text to block
      matched_string_tag: ["<em class='search_highlight'>", "</em>"] //surround matched text with
    };

    function replace_matched_string(old_string) { //highlights text with matched_string_tag in opts
      if (search_term === undefined || search_term === "" || old_string.toLowerCase().indexOf(search_term.toLowerCase()) == -1) {
        return old_string;
      }
      if (old_string === "") {
        return "";
      }
      const match_start = old_string.toLowerCase().indexOf("" + search_term.toLowerCase() + "");
      const match_end = match_start + search_term.length - 1;
      const before_match = old_string.slice(0, match_start);
      const match_text = old_string.slice(match_start, match_end + 1);
      const after_match = old_string.slice(match_end + 1);
      const new_string = before_match + opts.matched_string_tag[0] + match_text + opts.matched_string_tag[1] + replace_matched_string(after_match);
      return new_string;
    }

    function get_element($this) { //gets to element from the text
      let i;
      for (i = 0; i < opts.ancestor_to_change; i++) {
        $this = $this.parent();
      }
      //allows widget to be chainable
      return $this;
    }

    function get_did_you_mean($results){
        let searchable_terms = $('#searchable_terms').data('searchableTerms');
        let closest_match_term = "no matches";
        let similarity_score = 100; //the lower the similarity_score the closer the match
        let current_score;
        searchable_terms.forEach(function(term){
          if ((current_score = Levenshtein.get(search_term,term)) < similarity_score) {
            closest_match_term = term;
            similarity_score = current_score;
          }
        });
        if (similarity_score <= Math.ceil(closest_match_term.length * 0.20)) {
          $results.append(`<h2 id="did_you_mean_suggestion">Did you mean <a class="sugguested_term_link">${closest_match_term}</a>?<h2>`);
        } else {
          $results.append(`<h2 id="did_you_mean_suggestion">No results found.<h2>`);
        }
    }

    function add_did_you_mean_listerner($results){
      $('.sugguested_term_link').click(function(){
        let search_bar = `#search_bar_${last_search_pos}`;
        $(search_bar).val($(this).text());
        $(search_bar).trigger('keyup');
        $(search_bar).trigger('focus');
      });
    }

  }(jQuery));
  /* End of jQuery Search Widget*/


  /*
  * Initialisation:
  * Attaches event listeners to the DOM
  * for the IndexSearch Widget
  */
  $(document).ready(function(){
    $("#search_bar_right").search();
    $("#search_bar_left").search();
    $("#search_bar_right").focus(function(){
      $('#search_bar_left').val('');
      last_search_pos = "right";
      $('#search_bar_right').trigger("keyup");
      $('#search_bar_right').css('opacity','1');
      $('#search_button_right').css('opacity','1');
      $('#search_bar_left').css('opacity','0.6');
      $('#search_button_left').css('opacity','0.6');
      show_results();
    });
    $("#search_bar_left").focus(function(){
      $('#search_bar_right').val('');
      last_search_pos = "left";
      $('#search_bar_left').trigger("keyup");
      $('#search_bar_left').css('opacity','1');
      $('#search_button_left').css('opacity','1');
      $('#search_bar_right').css('opacity','0.6');
      $('#search_button_right').css('opacity','0.6');
      show_results();
    });

    $('.result_item, .result_item_with_icon').click(function(event){
      event.stopPropagation();
      index_clicked($(this));
    });

    /*
    ******  Tooltip disabled for now *****
    *https://kazzkiq.github.io/balloon.css/*
    $('.result_item, .result_item_with_icon').mouseenter(function(){
    $(this).attr('data-balloon-pos','right');
    $(this).attr('data-balloon','Click to add to '+last_search_pos.toUpperCase()+' eye');
  });
  */

  $('body').append('<div id="dim_rest" class="ui-widget-overlay" style="position:fixed;display : none; width: 100%; height: 100%; z-index: 180;"></div>');
  $('body').append("<div id=\"is_loading\"style=\"display : none; position: fixed; background-color: black; width: 100%; height: 100%; z-index: 1000; opacity: 0.8; top:0px; \"><img class=\"is_loading\" style=\" position: fixed; z-index: 1000; height: 64px; width: 64px; top: 33%; left: 50%;\"></div>");
  $('#description_toggle').change(function(){
    let current_search_bar = "#search_bar_"+last_search_pos;
    if (this.checked) {
      $('.description_icon,.description_note').show();
      show_and_search_descriptions = true;
      $(current_search_bar).trigger('focus');
      $(current_search_bar).trigger('keyup');
    } else {
      $('.description_icon,.description_note').hide();
      show_and_search_descriptions = false;
      $(current_search_bar).trigger('focus');
      $(current_search_bar).trigger('keyup');
    }
    event.stopPropagation();
  });
  $('#children_toggle').change(function(){
    let current_search_bar = "#search_bar_"+last_search_pos;
    if (this.checked) {
      $(current_search_bar).trigger('focus');
      $(current_search_bar).trigger('keyup');
    } else {
      show_children = false;
      $(current_search_bar).trigger('focus');
      $(current_search_bar).trigger('keyup');
    }
    event.stopPropagation();
  });

  $(window).click(function() {
    hide_results();
  });

  $('#big_cross').click(function(){
    hide_results();
  });

  $('.switch,#results,#search_bar_right,#search_bar_left').click(function(){
    event.stopPropagation();
  });

  //prevents body mousedown event being triggered
  //as this would cause the doodle popup to hide
  $('#results').mousedown(function(){
    event.stopPropagation();
  });
  if ($('.event-actions li:contains(Create)').length > 0) {
    $('#search_options_container').css('width','260px');
  }
  let set_result_box_left = function() {
    let sidebar_left = $('.column.sidebar.episodes-and-events').offset().left;
    let sidebar_width = $('.column.sidebar.episodes-and-events').width();
    let result_box_left = sidebar_left+sidebar_width;
    $('#results').css('left','result_box_left'+'px');
  };
  set_result_box_left();
  $(window).resize(function() {
    set_result_box_left();
  });
  $('#search_button_right').click(function(event){
    $('#search_bar_right').trigger('focus');
    event.stopPropagation();
  });
  $('#search_button_left').click(function(event){
    event.stopPropagation();
    $('#search_bar_left').trigger('focus');
  });
});
/* End of Initialisation */


/*
* Auxilary Functions:
* Frequently used code
*/
function get_controls_id(elementId, position){
  return "#ed_canvas_edit_"+position+"_"+elementId+"_controls";
}

function get_doodle_button(elementId, doodleClassName, position) {
  let doodle_id = "#"+doodleClassName+position+"_"+elementId;
  let $item = $(doodle_id).children();
  return $item;
}

function show_results(){
  var body = document.body,
  html = document.documentElement;
  var height = Math.max( body.scrollHeight, body.offsetHeight,
    html.clientHeight, html.scrollHeight, html.offsetHeight );
    $('#dim_rest').css("height", height);
    $('#dim_rest').show();
    $("body").css("overflow","hidden");
    var $eventHeader = $('.event-header');
    $("#results").css({top: $eventHeader.offset().top + $eventHeader.height() });
    $("#results").show();
    $(".switch").show();
    $("#children_toggle_container,#description_toogle_container").css("display","inline-block");
    $('#search_options_container').css('background-color','#b1b6bb');
  }

  function hide_results(){
    $('#search_bar_right').css('opacity','1');
    $('#search_button_right').css('opacity','1');
    $('#search_bar_left').css('opacity','1');
    $('#search_button_left').css('opacity','1');
    $('#search_bar_right,#search_bar_left').val('');
    $('#results').scrollTop(0);
    $('#dim_rest').hide();
    $("body").css("overflow","auto");
    $("#results").hide();
    $(".switch").hide();
    $("#children_toggle_container,#description_toogle_container").css("display","none");
    $('#search_options_container').css('background-color','transparent');
  }
  /*End of Auxilary Functions*/


  /*
  * Action Code
  * Code that handles what happens when
  * an index is clicked
  */
  function index_clicked($this){
    let parameters = {};
    parameters["element_name"] = $this.data('elementName');
    parameters["element_id"] = $this.data('elementId');
    parameters["doodle_name"] = $this.data('doodleClassName');
    parameters["property_name"] = $this.data('property');
    parameters["goto_id"] = $this.data('gotoId');
    parameters["goto_subcontainer"] = $this.data('gotoSubcontainer');
    parameters["goto_tag"] = $this.data('gotoTag');
    parameters["goto_text"] = $this.data('gotoText');
    //can revese order have different length chains etc
    //Chains can be made conditional based on content of parameters
    //Guarantees funcion execution order (even for asyncrounous functions)

    let done = function() {
      $('#is_loading').hide();
      clearTimeout(is_loading_timeout);
      hide_results();
    };

    let start = function() {
      $('#is_loading').show();
      //if ajax call is very slow hide loading gif so user can perform other actions
      is_loading_timeout = setTimeout(()=>done(),6000);
    }

    //element -> doodle -> property
    if (parameters["doodle_name"]){
      start();
      click_element(parameters).then(result => click_doodle(result)).then(result => click_property(result)).catch(() => done());
      return;
    }

    //element -> id
    if (parameters["goto_id"]) {
      start();
      click_element(parameters).then(result => goto_id(result)).catch(() => done());
      return;
    }

    //element -> tag (subcontainers?) -> text
    if (parameters['goto_tag']) {
      start();
      click_element(parameters).then(result => goto_tag_and_text(result)).catch(() => done());
      return;
    }

    //element
    if (parameters['element_name']) {
      start();
      click_element(parameters).catch(() => done());
      return;
    }

    //if it gets here then the index item is not clickable as it has no element name

  }

  function goto_id(parameters){
    return new Promise(function(resolve, reject) {
      let target_id = parameters['goto_id'].replace('%position',last_search_pos);
      $(`section[data-element-type-id = ${parameters['element_id']}]`).find(`#${target_id}`).effect("highlight", {}, 6000);
      reject();
    });
  }

  function goto_tag_and_text(parameters){
    return new Promise(function(resolve, reject) {
      let container = $(`section[data-element-type-id = ${parameters['element_id']}]`);
      if (parameters['goto_subcontainer']) {
        container = container.find('.'+parameters['goto_subcontainer'].replace('%position',last_search_pos));
      }
      container.find(`${parameters['goto_tag']}:contains(${parameters['goto_text']})`).effect("highlight", {}, 6000); //if want whole row highlight hightlight parent if not div or fieldset
      reject();
    });
  }

  function click_element(parameters){
    //get side bar item
    let $item = $(`.oe-event-sidebar-edit a:contains(${parameters['element_name']})`).filter(function(){
      return $(this).text() == parameters['element_name'];
    });
    if (parameters['element_name'] == 'Risks'){
      $item = $(`.oe-event-sidebar-edit a:contains(${parameters['element_name']}):first`); //temp fix while there is two risks on side bar
    }
    return click_sidebar_element($item).then(function (){
      return new Promise(function(resolve, reject) {
        //see if parameters are set for doodle
        if (parameters['doodle_name'] || parameters['goto_id'] || parameters['goto_tag']) {
          resolve(parameters);
        } else {
          reject();
        }
      });
    });
  }

  function click_doodle(parameters){
    ED.Checker.storeCanvasId("ed_canvas_edit_"+last_search_pos+"_"+parameters.element_id);
    return onAllCanvasesReady().then(function(){
      return new Promise(function(resolve, reject) {
        let ed_canvas = ED.Checker.getInstanceByIdSuffix(last_search_pos+"_"+parameters.element_id);
        let dropdown_box_selector = "#eyedrawwidget_"+last_search_pos+"_"+parameters.element_id;
        let $doodle = get_doodle_button(parameters.element_id,parameters.doodle_name,last_search_pos);
        let doodle_name = ED.titles[parameters.doodle_name];
        let $selected_doodle = $(dropdown_box_selector).find("#ed_example_selected_doodle").children().find("option:contains("+doodle_name+")");
        if ($selected_doodle.length == 0) {
          ed_canvas.addDoodle(parameters.doodle_name);
        } else {
          $(dropdown_box_selector).find("#ed_example_selected_doodle").children().find("option").removeAttr('selected');
          $selected_doodle.attr('selected','selected');
          $(dropdown_box_selector).find("#ed_example_selected_doodle").trigger('change');
        }
        //Ensures Promise chains breaks if parameter(s) for next promise are not present
        if (parameters.property_name) {
          resolve(parameters);
        } else {
          reject();
        }
      });
    });
  }

  function click_property(parameters){
    return new Promise(function(resolve, reject) {
      let control_id = get_controls_id(parameters.element_id,last_search_pos);
      $(control_id).find("div:contains("+parameters.property_name+")").effect("highlight", {}, 6000);
      /* Breaks the Promise chain as nothing should be called after property,
      based on the current code */
      reject();
    });
  }
  //wrapper for old-style callback
  function click_sidebar_element($item) {
    return new Promise(function(resolve, reject) {
      event_sidebar.loadClickedItem($item,{},resolve);
    });
  }

  //wrapper for old-style callback
  function onAllCanvasesReady() {
    return new Promise(function(resolve, reject) {
      ED.Checker.onAllReady(resolve);
    });
  }
  /* End of Promise code */


  /* Shortcut plugin */
  /**
  * http://www.openjs.com/scripts/events/keyboard_shortcuts/
  * Version : 2.01.B
  * By Binny V A
  * License : BSD
  */
  const shortcut = {
    'all_shortcuts':{},//All the shortcuts are stored in this array
    'add': function(shortcut_combination,callback,opt) {
      //Provide a set of default options
      var default_options = {
        'type':'keyup',
        'propagate':false,
        'disable_in_input':false,
        'target':document,
        'keycode':false
      }
      if(!opt) opt = default_options;
      else {
        for(var dfo in default_options) {
          if(typeof opt[dfo] == 'undefined') opt[dfo] = default_options[dfo];
        }
      }

      var ele = opt.target;
      if(typeof opt.target == 'string') ele = document.getElementById(opt.target);
      var ths = this;
      shortcut_combination = shortcut_combination.toLowerCase();

      //The function to be called at keypress
      var func = function(e) {
        e = e || window.event;

        if(opt['disable_in_input']) { //Don't enable shortcut keys in Input, Textarea fields
        var element;
        if(e.target) element=e.target;
        else if(e.srcElement) element=e.srcElement;
        if(element.nodeType==3) element=element.parentNode;

        if(element.tagName == 'INPUT' || element.tagName == 'TEXTAREA') return;
      }

      //Find Which key is pressed
      let code;
      if (e.keyCode) code = e.keyCode;
      else if (e.which) code = e.which;
      var character = String.fromCharCode(code).toLowerCase();

      if(code == 188) character=","; //If the user presses , when the type is onkeyup
      if(code == 190) character="."; //If the user presses , when the type is onkeyup

      var keys = shortcut_combination.split("+");
      //Key Pressed - counts the number of valid keypresses - if it is same as the number of keys, the shortcut function is invoked
      var kp = 0;

      //Work around for stupid Shift key bug created by using lowercase - as a result the shift+num combination was broken
      var shift_nums = {
        "`":"~",
        "1":"!",
        "2":"@",
        "3":"#",
        "4":"$",
        "5":"%",
        "6":"^",
        "7":"&",
        "8":"*",
        "9":"(",
        "0":")",
        "-":"_",
        "=":"+",
        ";":":",
        "'":"\"",
        ",":"<",
        ".":">",
        "/":"?",
        "\\":"|"
      }
      //Special Keys - and their codes
      var special_keys = {
        'esc':27,
        'escape':27,
        'tab':9,
        'space':32,
        'return':13,
        'enter':13,
        'backspace':8,

        'scrolllock':145,
        'scroll_lock':145,
        'scroll':145,
        'capslock':20,
        'caps_lock':20,
        'caps':20,
        'numlock':144,
        'num_lock':144,
        'num':144,

        'pause':19,
        'break':19,

        'insert':45,
        'home':36,
        'delete':46,
        'end':35,

        'pageup':33,
        'page_up':33,
        'pu':33,

        'pagedown':34,
        'page_down':34,
        'pd':34,

        'left':37,
        'up':38,
        'right':39,
        'down':40,

        'f1':112,
        'f2':113,
        'f3':114,
        'f4':115,
        'f5':116,
        'f6':117,
        'f7':118,
        'f8':119,
        'f9':120,
        'f10':121,
        'f11':122,
        'f12':123
      }

      var modifiers = {
        shift: { wanted:false, pressed:false},
        ctrl : { wanted:false, pressed:false},
        alt  : { wanted:false, pressed:false},
        meta : { wanted:false, pressed:false}	//Meta is Mac specific
      };

      if(e.ctrlKey)	modifiers.ctrl.pressed = true;
      if(e.shiftKey)	modifiers.shift.pressed = true;
      if(e.altKey)	modifiers.alt.pressed = true;
      if(e.metaKey)   modifiers.meta.pressed = true;

      for(var i=0; k=keys[i],i<keys.length; i++) {
        //Modifiers
        if(k == 'ctrl' || k == 'control') {
          kp++;
          modifiers.ctrl.wanted = true;

        } else if(k == 'shift') {
          kp++;
          modifiers.shift.wanted = true;

        } else if(k == 'alt') {
          kp++;
          modifiers.alt.wanted = true;
        } else if(k == 'meta') {
          kp++;
          modifiers.meta.wanted = true;
        } else if(k.length > 1) { //If it is a special key
          if(special_keys[k] == code) kp++;

        } else if(opt['keycode']) {
          if(opt['keycode'] == code) kp++;

        } else { //The special keys did not match
          if(character == k) kp++;
          else {
            if(shift_nums[character] && e.shiftKey) { //Stupid Shift key bug created by using lowercase
              character = shift_nums[character];
              if(character == k) kp++;
            }
          }
        }
      }

      if(kp == keys.length &&
        modifiers.ctrl.pressed == modifiers.ctrl.wanted &&
        modifiers.shift.pressed == modifiers.shift.wanted &&
        modifiers.alt.pressed == modifiers.alt.wanted &&
        modifiers.meta.pressed == modifiers.meta.wanted) {
          callback(e);

          if(!opt['propagate']) { //Stop the event
            //e.cancelBubble is supported by IE - this will kill the bubbling process.
            e.cancelBubble = true;
            e.returnValue = false;

            //e.stopPropagation works in Firefox.
            if (e.stopPropagation) {
              e.stopPropagation();
              e.preventDefault();
            }
            return false;
          }
        }
      }
      this.all_shortcuts[shortcut_combination] = {
        'callback':func,
        'target':ele,
        'event': opt['type']
      };
      //Attach the function with the event
      if(ele.addEventListener) ele.addEventListener(opt['type'], func, false);
      else if(ele.attachEvent) ele.attachEvent('on'+opt['type'], func);
      else ele['on'+opt['type']] = func;
    },

    //Remove the shortcut - just specify the shortcut and I will remove the binding
    'remove':function(shortcut_combination) {
      shortcut_combination = shortcut_combination.toLowerCase();
      var binding = this.all_shortcuts[shortcut_combination];
      delete(this.all_shortcuts[shortcut_combination])
      if(!binding) return;
      var type = binding['event'];
      var ele = binding['target'];
      var callback = binding['callback'];

      if(ele.detachEvent) ele.detachEvent('on'+type, callback);
      else if(ele.removeEventListener) ele.removeEventListener(type, callback, false);
      else ele['on'+type] = false;
    }
  }

  shortcut.add("Ctrl+Alt+R",function() {
    $("#search_bar_right").trigger("focus");
  });
  shortcut.add("Ctrl+Alt+L",function() {
    $("#search_bar_left").trigger("focus");
  });
  shortcut.add("Esc",function() {
    $("#search_bar_right,#search_bar_left").trigger("blur");
    hide_results();
  });
  /* End of Shortcut code */

  /* https://github.com/hiddentao/fast-levenshtein */

  (function() {
    'use strict';

    var collator;
    try {
      collator = (typeof Intl !== "undefined" && typeof Intl.Collator !== "undefined") ? Intl.Collator("generic", { sensitivity: "base" }) : null;
    } catch (err){
      console.log("Collator could not be initialized and wouldn't be used");
    }
    // arrays to re-use
    var prevRow = [],
      str2Char = [];

    /**
     * Based on the algorithm at http://en.wikipedia.org/wiki/Levenshtein_distance.
     */
    var Levenshtein = {
      /**
       * Calculate levenshtein distance of the two strings.
       *
       * @param str1 String the first string.
       * @param str2 String the second string.
       * @param [options] Additional options.
       * @param [options.useCollator] Use `Intl.Collator` for locale-sensitive string comparison.
       * @return Integer the levenshtein distance (0 and above).
       */
      get: function(str1, str2, options) {
        var useCollator = (options && collator && options.useCollator);

        var str1Len = str1.length,
          str2Len = str2.length;

        // base cases
        if (str1Len === 0) return str2Len;
        if (str2Len === 0) return str1Len;

        // two rows
        var curCol, nextCol, i, j, tmp;

        // initialise previous row
        for (i=0; i<str2Len; ++i) {
          prevRow[i] = i;
          str2Char[i] = str2.charCodeAt(i);
        }
        prevRow[str2Len] = str2Len;

        var strCmp;
        if (useCollator) {
          // calculate current row distance from previous row using collator
          for (i = 0; i < str1Len; ++i) {
            nextCol = i + 1;

            for (j = 0; j < str2Len; ++j) {
              curCol = nextCol;

              // substution
              strCmp = 0 === collator.compare(str1.charAt(i), String.fromCharCode(str2Char[j]));

              nextCol = prevRow[j] + (strCmp ? 0 : 1);

              // insertion
              tmp = curCol + 1;
              if (nextCol > tmp) {
                nextCol = tmp;
              }
              // deletion
              tmp = prevRow[j + 1] + 1;
              if (nextCol > tmp) {
                nextCol = tmp;
              }

              // copy current col value into previous (in preparation for next iteration)
              prevRow[j] = curCol;
            }

            // copy last col value into previous (in preparation for next iteration)
            prevRow[j] = nextCol;
          }
        }
        else {
          // calculate current row distance from previous row without collator
          for (i = 0; i < str1Len; ++i) {
            nextCol = i + 1;

            for (j = 0; j < str2Len; ++j) {
              curCol = nextCol;

              // substution
              strCmp = str1.charCodeAt(i) === str2Char[j];

              nextCol = prevRow[j] + (strCmp ? 0 : 1);

              // insertion
              tmp = curCol + 1;
              if (nextCol > tmp) {
                nextCol = tmp;
              }
              // deletion
              tmp = prevRow[j + 1] + 1;
              if (nextCol > tmp) {
                nextCol = tmp;
              }

              // copy current col value into previous (in preparation for next iteration)
              prevRow[j] = curCol;
            }

            // copy last col value into previous (in preparation for next iteration)
            prevRow[j] = nextCol;
          }
        }
        return nextCol;
      }

    };

    // amd
    if (typeof define !== "undefined" && define !== null && define.amd) {
      define(function() {
        return Levenshtein;
      });
    }
    // commonjs
    else if (typeof module !== "undefined" && module !== null && typeof exports !== "undefined" && module.exports === exports) {
      module.exports = Levenshtein;
    }
    // web worker
    else if (typeof self !== "undefined" && typeof self.postMessage === 'function' && typeof self.importScripts === 'function') {
      self.Levenshtein = Levenshtein;
    }
    // browser main thread
    else if (typeof window !== "undefined" && window !== null) {
      window.Levenshtein = Levenshtein;
    }
  }());

  /* End of https://github.com/hiddentao/fast-levenshtein */
}(this.OpenEyes.UI.Widgets));
