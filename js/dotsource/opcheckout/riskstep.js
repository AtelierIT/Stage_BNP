var DotsourceRisk = Class.create();
DotsourceRisk.prototype = {
    initialize: function(form, loadWaiting, requestUrl, onSuccess) {
		this.form				= form;
		this.requestUrl			= requestUrl;
		this.loadWaiting		= loadWaiting;
		
		this.onComplete			= this.resetLoadWaiting.bindAsEventListener(this);
		this.onSave				= this.nextStep.bindAsEventListener(this);
		
		//Add the additional success function
		if (typeof onSuccess == "function") {
			this.onSuccess 		= onSuccess;
		} else {
			this.onSuccess 		= undefined;
		}
    },

    save: function() {
        if (checkout.loadWaiting != false) {
        	return;
        }
        
        //Validate form
        var validator = new Validation(this.form);
        if (!validator.validate()) {
        	return;
        }
        
        //Active loading html
        checkout.setLoadWaiting(this.loadWaiting);
        
        //Send the request
        var request = new Ajax.Request(
        		this.requestUrl,
            {
                method		: 'post',
                parameters	: Form.serialize(this.form),
                onComplete	: this.onComplete,
                onSuccess	: this.onSave,
                onFailure	: checkout.ajaxFailure.bind(checkout),
            }
        );
    },

    resetLoadWaiting: function(transport) {
    	checkout.setLoadWaiting(false);
    },

    nextStep: function(transport) {
        if (transport && transport.responseText){
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
            	var message = "The request can't be processed correctly. Please try again.";
            	
                if(typeof Translator == "object") {
                	message = Translator.translate(message);
                }
                
            	alert(message);
            	return;
            }
        }

        if (response.error){
            if ((typeof response.message) == 'string') {
                alert(response.message);
            } else {
                alert(response.message.join("\n"));
            }

            return false;
        }
        
        //Reset the loading html
        checkout.setLoadWaiting(false);
        
        //Update the content
        checkout.setStepResponse(response);
        
        //Call the additional on success method
        if (typeof this.onSuccess == "function") {
        	this.onSuccess();
        }
    }
}