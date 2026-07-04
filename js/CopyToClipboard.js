 function CreateElementForExecCommand (textToClipboard) {
    		var forExecElement = document.createElement ("div");
    		forExecElement.style.position = "absolute";
    		forExecElement.style.left = "-10000px";
    		forExecElement.style.top = "-10000px";
    		forExecElement.textContent = textToClipboard;
    		document.body.appendChild (forExecElement);
    		forExecElement.contentEditable = true;

    		return forExecElement;
    	}
        
        function SelectContent (element) {
    		var rangeToSelect = document.createRange ();
    		rangeToSelect.selectNodeContents (element);

    		var selection = window.getSelection ();
    		selection.removeAllRanges ();
    		selection.addRange (rangeToSelect);
    	}
        
        function CopyToClipboard (input) {
    		var textToClipboard = input;

    		var success = true;
    		if (window.clipboardData) {
    		    window.clipboardData.setData ("Text", textToClipboard);
    		}
    		else {
    		    var forExecElement = CreateElementForExecCommand (textToClipboard);
    		    SelectContent (forExecElement);

    		    var supported = true;
    		    try {
    		        if (window.netscape && netscape.security) {
    		            netscape.security.PrivilegeManager.enablePrivilege ("UniversalXPConnect");
    		        }

    		        success = document.execCommand ("copy", false, null);
    		    }
    		    catch (e) {
    		        success = false;
    		    }

    		    document.body.removeChild (forExecElement);
    		}

    		return success;
    	}