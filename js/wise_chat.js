/**
 * Wise Chat core controller.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatController(options) {
	var messagesHistory = new WiseChatMessagesHistory();
	var imageViewer = new WiseChatImageViewer();
	var dateFormatter = new WiseChatDateFormatter();
	var messageAttachments = new WiseChatMessageAttachments(options, imageViewer);
	var messages = new WiseChatMessages(options, messagesHistory, messageAttachments, dateFormatter);
	var settings = new WiseChatSettings(options, messages);
	var actionsExecutor = new WiseChatActionsExecutor(options, messages);
	
	messages.start();
	actionsExecutor.start();
};

/**
 * WiseChatDateFormatter class. Formats dates given in UTC timezone.
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatDateFormatter() {
	
	function makeLeadZero(number) {
		return (number < 10 ? '0' : '') + number;
	}
	
	/**
	* Parses date given in ISO format.
	* 
	* @param {String} isoDate Date in ISO format
	* 
	* @return {Date} Parsed date
	*/
	function parseISODate(isoDate) {
		var s = isoDate.split(/\D/);
		
		return new Date(Date.UTC(s[0], --s[1]||'', s[2]||'', s[3]||'', s[4]||'', s[5]||'', s[6]||''))
	}
	
	/**
	* Returns formatted date.
	* 
	* @param {Date} date Date to format as a string
	* @param {String} format Desired date format
	* 
	* @return {String} Formatted date
	*/
	function formatDate(date, format) {
		format = format.replace(/Y/, date.getFullYear());
		format = format.replace(/m/, makeLeadZero(date.getMonth() + 1));
		format = format.replace(/d/, makeLeadZero(date.getDate()));
		format = format.replace(/H/, makeLeadZero(date.getHours()));
		format = format.replace(/i/, makeLeadZero(date.getMinutes()));
		
		return format;
	}
	
	// public API:
	this.formatDate = formatDate;
	this.parseISODate = parseISODate;
};

/**
 * WiseChatMessageAttachments class. Manages attachments preparation. 
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatMessageAttachments(options, imageViewer) {
	var IMAGE_MAX_WIDTH = 1000;
	var IMAGE_MAX_HEIGHT = 1000;
	var IMAGE_TYPES = ['jpg', 'jpeg', 'tiff', 'png', 'bmp', 'gif'];
	var container = jQuery('#' + options.chatId);
	var messageAttachmentsPanel = container.find('.wcMessageAttachments');
	var imageUploadPreviewImage = container.find('.wcImageUploadPreview');
	var imageUploadFile = container.find('.wcImageUploadFile');
	var imageUploadClearButton = container.find('.wcImageUploadClear');
	var attachments = [];
	
	var canvas = container.find('.wcCanvasTemp');
	if (canvas.length === 0) {
		container.append('<canvas class="wcCanvasTemp" style="display:none;"> </canvas>');
		canvas = container.find('.wcCanvasTemp');
	}
	canvas = canvas[0];
	
	function addAttachment(type, data) {
		attachments.push({ type: type, data: data });
	}
	
	function showImageAttachment() {
		if (attachments.length > 0 && attachments[0].type === 'image') {
			imageViewer.show(attachments[0].data);
		}
	}
	
	function onImageUploadFileChange() {
		var fileInput = imageUploadFile[0];
		if (typeof FileReader === 'undefined' || fileInput.files.length === 0) {
			return;
		}
		
		var fileReader = new FileReader();
		var fileDetails = fileInput.files[0];
		var extension = fileDetails.name.split('.').pop().toLowerCase();
		if (IMAGE_TYPES.indexOf(extension) > -1) {
			if (extension === 'jpg') {
				extension = 'jpeg';
			}
			var mimeType = 'image/' + extension;
			fileReader.onload = function(event) {
				clearAttachments();
				resizeImageAndAddToAttachments(event.target.result, mimeType);
			};
			fileReader.readAsDataURL(fileDetails);
		}
	}
	
	function resizeImageAndAddToAttachments(imageSource, mimeType) {
		var tempImage = document.createElement("img");
		tempImage.onload = function () {
			var context = canvas.getContext("2d");
			context.drawImage(tempImage, 0, 0);
			
			var width = tempImage.width;
			var height = tempImage.height;
			if (width > height) {
				if (width > IMAGE_MAX_WIDTH) {
					height *= IMAGE_MAX_WIDTH / width;
					width = IMAGE_MAX_WIDTH;
				}
			} else {
				if (height > IMAGE_MAX_HEIGHT) {
					width *= IMAGE_MAX_HEIGHT / height;
					height = IMAGE_MAX_HEIGHT;
				}
			}
			canvas.width = width;
			canvas.height = height;
			context.drawImage(tempImage, 0, 0, width, height);
			
			imageSource = canvas.toDataURL(mimeType);
			addAttachment('image', imageSource);
			imageUploadPreviewImage.attr('src', imageSource);
			messageAttachmentsPanel.show();
			imageUploadFile.val('');
		}
		
		tempImage.src = imageSource;
	}
	
	/**
	* Returns array of prepared attachments.
	* 
	* @return {Array}
	*/
	function getAttachments() {
		return attachments;
	}
	
	/**
	* Clears all prepared attachments.
	*/
	function clearAttachments() {
		attachments = [];
		messageAttachmentsPanel.hide();
	}
	
	// DOM events:
	imageUploadFile.change(onImageUploadFileChange);
	imageUploadClearButton.click(clearAttachments);
	imageUploadPreviewImage.click(showImageAttachment);
	
	// public API:
	this.getAttachments = getAttachments;
	this.clearAttachments = clearAttachments;
};

/**
 * WiseChatImageViewer
 *
 * @version 1.0
 * @author Marcin Ławrowski <marcin.lawrowski@gmail.com>
 */
function WiseChatImageViewer() {
	var HOURGLASS_ICON = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wQEDB4ktAYXpwAAAb5JREFUSMe1lr9qFFEUh78rg8gWW1ikSLEWgkVq2SoYsbBIk1dYEAsxaJt3sLAIFkEEX0FSRlgMhKAPkEIQwZDChATSBLMQP5uz4bKZmZ3ZxR+cYs75nT9z7rlnJpFBfQC8B24xG/4Cz1NK38eKYoKwADxiPiwA1wnSpFUdAO+A+y0D/wBeppQ+5sqihHgAdIBRSumsSWT1bvgcNCF31Et1tWnp6mr4dCZtNw4zpXQB7AJrLdqzBuyGb6OKBuq52m3A7QZ3UGZPVW0CfgJvgc/As4r4H4CnwGvgXkrpDy36uh6VPVRPvYnTsJ2r662HWS3U/ZDH6kkW/CR0Y3sx041Re+qh+kXtq59C+qE7VHt1MWpXQkrpF7ACdIFhZhqGbiU4syX474gWHUU7FjP9YuiOprVo2iF/jUO8U3Hj94NTzJLgVYxgL0v4JqTI3rD9mEZ1v9WN7Hk7G9Pt8d5RN4LbaZPgelWE7JVctL3MXrkqqhLsqFvqbXVoNYbB2VJ32rTnMlbwptOxWbeuyxL0w/GJetUgwVVwVfuT8crGawm4AEbAi4ZdHYXPEvCtrvpl58dy3Rscx9dsnt+W41zxD60+eUN8VNiNAAAAAElFTkSuQmCC";
	
	var container = jQuery('body');
	var imagePreviewFade = container.find('.wcImagePreviewFade');
	var imagePreview = container.find('.wcImagePreview');
	if (imagePreviewFade.length === 0) {
		container.append('<div class="wcImagePreview"> </div><div class="wcImagePreviewFade"> </div>');
		imagePreviewFade = container.find('.wcImagePreviewFade');
		imagePreview = container.find('.wcImagePreview');
	}
	
	function show(imageSource) {
		clearRemnants();
		
		imagePreviewFade.show();
		addAndShowHourGlass();
		
		var imageElement = jQuery('<img style="display:none;" />');
		imageElement.on('load', function() {
			removeHourGlass();
			
			var image = jQuery(this);
			var additionalMargin = 20;
			var windowWidth = jQuery(window).width();
			var windowHeight = jQuery(window).height();
			image.show();
			
			if (image.width() > windowWidth && image.height() > windowHeight) {
				if (image.width() > image.height()) {
					image.width(windowWidth - additionalMargin);
				} else {
					image.height(windowHeight - additionalMargin);
				}
			} else if (image.width() > windowWidth) {
				image.width(windowWidth - additionalMargin);
			} else if (image.height() > windowHeight) {
				image.height(windowHeight - additionalMargin);
			}
			
			var topPosition = Math.max(0, ((windowHeight - jQuery(this).outerHeight()) / 2) + jQuery(window).scrollTop());
			var leftMargin = -1 * (image.width() / 2);
			imagePreview.css({
				top: topPosition + "px",
				marginLeft: leftMargin + "px"
			});
		});
		imageElement.attr('src', imageSource);
		imageElement.appendTo(imagePreview);
		imageElement.click(hide);
	}
	
	function hide() {
		clearRemnants();
		imagePreview.hide();
		imagePreviewFade.hide();
	}
	
	function clearRemnants() {
		imagePreview.find('img').remove();
	}
	
	function addAndShowHourGlass() {
		var windowHeight = jQuery(window).height();
		var imageElement = jQuery('<img class="wcHourGlass" />');
		var topPosition = Math.max(0, ((windowHeight - 24) / 2) + jQuery(window).scrollTop());
		
		imageElement.attr('src', HOURGLASS_ICON);
		imageElement.appendTo(imagePreview);
		imagePreview.css({
			top: topPosition + "px",
			marginLeft: "-12px"
		});
		imagePreview.show();
	}
	
	function removeHourGlass() {
		container.find('.wcHourGlass').remove();
	}
	
	// DOM events:
	imagePreviewFade.click(hide);
	
	// public API:
	this.show = show;
	this.hide = hide;
};