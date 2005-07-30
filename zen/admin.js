/* Zenphoto administration javascript. */

function addUploadBoxes(placeholderid, copyfromid, num) {
  var placeholder = document.getElementById(placeholderid);
  var copyfrom = document.getElementById(copyfromid);
  for (i=0; i<num; i++) {
    if (window.totalinputs >= 50) return;
    var newdiv = document.createElement('div');
    newdiv.innerHTML = copyfrom.innerHTML;
    newdiv.className = copyfrom.className;
    placeholder.parentNode.insertBefore(newdiv, placeholder);
    window.totalinputs++;
  }
}

function albumSwitch(sel) {
  var selected = sel.options[sel.selectedIndex];
  var albumtext = document.getElementById("albumtext");
  var albumbox = document.getElementById("folderdisplay");
  var titlebox = document.getElementById("albumtitle");
  if (selected.value == "") {            
    albumtext.style.display = "block";
    albumbox.value = "";
    titlebox.value = "";
  } else {
    albumtext.style.display = "none";
    albumbox.value = selected.value;
    titlebox.value = selected.text;
  }

}

function contains(arr, key) {
  for (i=0; i<arr.length; i++) {
    if (arr[i].toLowerCase() == key.toLowerCase()) {
      return true;
    }
  }
  return false;
}

function updateFolder(nameObj, folderID, checkboxID) {
  var autogen = document.getElementById(checkboxID).checked;
  var folder = document.getElementById(folderID);
  var name = nameObj.value;
  var fname = "";
  var fnamesuffix = "";
  var count = 1;
  if (autogen && name != "") {
    fname = name;
    fname = fname.toLowerCase();
    fname = fname.replace(/[\!@#$\%\^&*()\~`\'\"]/gi, "");
    fname = fname.replace(/[^a-zA-Z0-9]/gi, "-");
    fname = fname.replace(/--*/gi, "-");
    while (contains(albumArray, fname+fnamesuffix)) {
      fnamesuffix = "-"+count;
      count++;
    }
  }    
  folder.value = fname+fnamesuffix;
}

function validateFolder(folderObj) {
  var errorDiv = document.getElementById("foldererror");
  if (albumArray && contains(albumArray, folderObj.value)) {
    errorDiv.style.display = "block";
  } else {
    errorDiv.style.display = "none";
  }
}

function toggleAutogen(fieldID, nameID, checkbox) {
  var field = document.getElementById(fieldID);
  var name = document.getElementById(nameID);
  if (checkbox.checked) {
    field.disabled = true;
    updateFolder(name, fieldID, checkbox.id);
  } else {
    field.disabled = false;
  }
}
        
