window[bloggerCustomVars.pluginSlug] = {
	dateFormat: "M d, yy"
};

(function( $ ) {
	'use strict';
	window[bloggerCustomVars.pluginSlug].setupFormValidation = (formId, rules, onValid = null) => {
		//console.log({formId, rules, onValid})
		return FormValidation.formValidation(document.getElementById(formId), {
			fields: rules,
			plugins: {
				message: new FormValidation.plugins.Message({
					container: function(){
						let target = $(arguments[1]).parents('td').first().find('.description-error');
						let container;

						if (target.length <= 0) {
							container = $('<p class="description description-error"></p>').get(0);
							$(arguments[1]).parent().append(container)
						} else {
							container = target.get(0);
						}

						return container
					}
				}),
				trigger: new FormValidation.plugins.Trigger(),
				submitButton: new FormValidation.plugins.SubmitButton(),
				//defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
				excluded: new FormValidation.plugins.Excluded(),
			},
		}).on('core.field.invalid', function(event) {
			const field = document.getElementById(formId).querySelector(`[name="${event}"]`);
			if(field){
				$(field).addClass('field-error');
			}

			$(field).parents('td').first().find('.description:not(.description-error)').hide();

			/*const submitButton = $(document.getElementById(formId)).find('input[type="submit"]');
			if(submitButton.length > 0){
				submitButton.prop('disabled', true)
			}*/
		}).on('core.field.valid', function(event) {
			const field = document.getElementById(formId).querySelector(`[name="${event}"]`);
			if(field){
				$(field).removeClass('field-error');
			}

			$(field).parents('td').first().find('.description:not(.description-error)').show();
		}).on('core.form.invalid', function(event) {
			console.log(event)
		}).on('core.form.valid', function(event) {
			if(onValid){
				onValid();
			}
		});
	}

	window[bloggerCustomVars.pluginSlug].showNotice = (type, message, hideDelay = 5000) => {
		const notice = $(`<div class="notice notice-${type} is-dismissible" style="display: none;"><p>${message}</p></div>`);
		$('.wrap h1').after(notice);
		notice.slideDown();

		setTimeout(() => {
			notice.slideUp();
		}, hideDelay)
	};

	window[bloggerCustomVars.pluginSlug].getDate = function( element ) {
		let date;

		try {
			date = $.datepicker.parseDate( window[bloggerCustomVars.pluginSlug].dateFormat, element.value );
		} catch( error ) {
			date = null;
		}

		return date;
	}

	$(document).ready(function() {
		const urlParams = new URLSearchParams(window.location.search);
		if(urlParams.get(`${bloggerCustomVars.pluginSlug}_redirect`)){
			let redirect_url = new URL(window.location.href);
			switch(urlParams.get(`${bloggerCustomVars.pluginSlug}_redirect`)){
				case 'projects':
					redirect_url.searchParams.delete('action');
					redirect_url.searchParams.delete('project');
					redirect_url.searchParams.delete(`${bloggerCustomVars.pluginSlug}_redirect`);
					break;
			}

			if(redirect_url){
				window.location.replace(redirect_url.href);
			}
		}
	});

})( jQuery );
