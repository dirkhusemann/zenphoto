

// Get the element's style (computed) for the attribute given.
function getStyle(element, attribute) { 
  if (element.currentStyle) {
    // Internet Explorer:
    if (attribute == "font-family") attribute = "fontFamily";
    else if (attribute == "font-size") attribute = "fontSize";
    else if (attribute == "font-weight") attribute = "fontWeight";
    return element.currentStyle[attribute];
  } else if (document.defaultView.getComputedStyle) {
    // Mozilla or other dignified browser:
    return document.defaultView.getComputedStyle(element, '').getPropertyValue(attribute);
  } else {
    return false;
  }
}


function convertLineBreaks(text) {
  return text.replace(/\n/g, '<br />\n');
}

function unconvertLineBreaks(text) {
  return text.replace(/(<br \/>)|(<br\/>)|(<br>)/gi, '\n');
}

function addBlankLine(text) {
  return text+"&nbsp;";
}

function stripSlashes(text) {
  text = text.replace(/\\\\/g, '%%%%%');
  text = text.replace(/\\/g, '');
  return text.replace(/%%%%%/g, '\\');
}

 ///////////////////////////////////////////////////////////////////////////////
// Makes a DIV into an editable Title field. 
function initEditableTitle(divID) {
  var div = document.getElementById(divID);
  div.blankMessage = "Click to add a title...";
  div.editMessage = "Click to edit title...";
    
  div.saveChanges = function(form) {
    var formDiv = form.parentNode;
    document.callbackdiv = this;
    this.innerHTML = "Saving...";
    x_saveTitle(form.content.value, this.saveChangesCB);
    return this.stopEditing();
  }
  
  div.getInputField = function () {
    return '<input type="text" name="content" value="'+this.innerHTML
      +'" style="font-size: '+this.style_fontSize
      +'; font-family: '+this.style_fontFamily
      +'; font-weight: '+this.style_fontWeight
      +'; font-style: '+this.style_fontStyle
      +';" size="'+(this.innerHTML.length)+'"/>';
  }
  
  initEditableDiv(divID);
}

 ///////////////////////////////////////////////////////////////////////////////
// Makes a DIV into an editable Tags field. 
function initEditableTags(divID) {
  var div = document.getElementById(divID);
  div.blankMessage = "Click to add tags...";
  div.editMessage = "Click to edit tags...";
    
  div.saveChanges = function(form) {
    var formDiv = form.parentNode;
    document.callbackdiv = this;
    this.innerHTML = "Saving...";
    x_saveTags(form.content.value, this.saveChangesCB);
    return this.stopEditing();
  }
  
  div.getInputField = function () {
    return '<input type="text" name="content" value="'+this.innerHTML
      +'" style="font-size: '+this.style_fontSize
      +'; font-family: '+this.style_fontFamily
      +'; font-weight: '+this.style_fontWeight
      +'; font-style: '+this.style_fontStyle
      +';" size="'+(this.innerHTML.length)+'"/>';
  }
  
  initEditableDiv(divID);
}

 ///////////////////////////////////////////////////////////////////////////////
// Makes a DIV into an editable Description field.
function initEditableDesc(divID) {
  var div = document.getElementById(divID);
  div.blankMessage = "Click to add a description...";
  div.editMessage = "Click to edit description...";

  
  div.saveChanges = function(form) {
    var formDiv = form.parentNode;
    document.callbackdiv = this;
    this.innerHTML = "Saving...";
    x_saveDesc(form.content.value, this.saveChangesCB);
    document.getElementById("zenphototempdiv").style.display = "none";
    return this.stopEditing();
  }
  
  div.getInputField = function () {
    var tempDiv = document.getElementById("zenphototempdiv");
    if (!tempDiv) {
      tempDiv = document.createElement('div');
      tempDiv.style.position = "absolute";
      tempDiv.style.width = this.style_width;
      tempDiv.style.top = "-5000px";
      tempDiv.id = "zenphototempdiv";
      this.parentNode.insertBefore(tempDiv, this);
    } else {
      tempDiv.style.display = this.style_display;
    }

    return '<textarea name="content" style="font-size: '+this.style_fontSize
      +'; font-family: '+this.style_fontFamily
      +'; font-weight: '+this.style_fontWeight
      +'; font-style: '+this.style_fontStyle
      +'; width: '+this.style_width+'; height: '+this.style_height
      +'; margin: 0px;" rows="5" cols="50"></textarea><br />';
  }
  
  // NOTE: in this function, *this* refers to an input field.
  div.contentOnKeyUp = function () {
    var tempdiv = document.getElementById("zenphototempdiv");
    tempdiv.innerHTML = addBlankLine(convertLineBreaks(this.value));
    this.style.height = getStyle(tempdiv, 'height');
  }
  
  initEditableDiv(divID);
}

  ///////////////////////////////////////////////////////////////////////////////
 // Makes any DIV editable, but not by itself: it needs several variables
//  defined before it will work (see examples above).
function initEditableDiv(divID) {
  var div = document.getElementById(divID);

  div.title = div.editMessage;
  
  div.style_display = getStyle(div, "display");
  div.style_fontFamily = getStyle(div, "font-family");
  div.style_fontSize = getStyle(div, "font-size");
  div.style_fontWeight = getStyle(div, "font-weight");
  div.style_fontStyle = getStyle(div, "font-style");
  div.style_width = getStyle(div, "width");
  div.style_height = getStyle(div, "height");
  
  if (div.innerHTML) {
    div.textValue = div.innerHTML;
  } else {
    div.textValue = div.textContent;
  }
  
  if (trim(div.innerHTML) == '' || trim(div.textValue) == "") {
    div.innerHTML = div.blankMessage;
  }
  
  div.startEditing = function () {
    if (document.currentlyEditing && document.currentlyEditing != this) return;
    document.currentlyEditing = this;
    this.isEditing = true;
		this.unhilight();
		var formDiv = this.getFormDiv();
    formDiv.form.content.value = unconvertLineBreaks(this.textValue);
		formDiv.style.display = this.style_display;
    this.style.display = "none";
    var form = formDiv.firstChild;
		// form.content.focus();
		form.content.select();
  }
  
  div.onclick = div.startEditing;
  
  div.getFormDiv = function () {
		if (!this.formDiv) {
			this.formDiv = document.createElement('div');
			this.parentNode.insertBefore(this.formDiv, this);
			this.formDiv.displayDiv = this;
    }
    // Refresh the dimensions.
    this.style_width = getStyle(div, "width");
    this.style_height = getStyle(div, "height");
    
    var formHTML = '<form onsubmit="this.content.blur(); this.parentNode.displayDiv.saveChanges(this); return false;">';
    formHTML += this.getInputField();
    formHTML += ' <input type="submit" value="save" \/>&nbsp;<input type="button" value="cancel" onclick="this.form.parentNode.displayDiv.stopEditing(); return false;" \/><\/form>';
    this.formDiv.innerHTML = formHTML;
    this.formDiv.form = this.formDiv.firstChild;
    this.formDiv.form.style.display = this.style_display;
    if (this.contentOnKeyUp) {
      this.formDiv.form.content.onkeyup = this.contentOnKeyUp;
    }
    
		return this.formDiv;
  }

  
  div.saveChangesCB = function(savedText) {
    displayDiv = document.callbackdiv;
    if (savedText == "") {
      displayDiv.innerHTML = displayDiv.blankMessage;
      displayDiv.textValue = "";
    } else {
      displayDiv.innerHTML = convertLineBreaks(savedText);
      displayDiv.textValue = savedText;
    }
  }
  
  div.stopEditing = function() {
    var formDiv = this.getFormDiv();
    formDiv.displayDiv.style.display = this.style_display;
    formDiv.style.display = "none";
    document.currentlyEditing = false;
    return false;
  }
  
  div.onmouseover = function() {
    if (!document.currentlyEditing) {
      this.hilight();
    } else {
      this.title = "Currently editing something else, save or cancel to edit this.";
    }
  }
  div.onmouseout = function() { 
    this.unhilight();
    this.title = this.editMessage;
  }
  
  div.hilight = function () { this.style.backgroundColor = "#FFFFD3"; }
  div.unhilight = function () { this.style.backgroundColor = ""; }
  
}

// Simple string-trimming function
function trim(s) {
  var t = s.substring(0,s.length);
  while (t.substring(0,1) == ' ') {
    t = t.substring(1,t.length);
  }
  while (t.substring(t.length-1,t.length) == ' ') {
    t = t.substring(0,t.length-1);
  }
  return t;
}

