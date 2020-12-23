if (typeof jQuery === "undefined") {
    throw new Error("jQuery plugins need to be before this file");
}
$.VideoNotes = {};

$.VideoNotes.input = {
    activate: function ($parentSelector) {
        $parentSelector = $parentSelector || $('body');

        $parentSelector.find('.form-control').focus(function () {
            $(this).closest('.form-line').addClass('focused');
        });

        $parentSelector.find('.form-control').focusout(function () {
            var $this = $(this);
            if ($this.parents('.form-group').hasClass('form-float')) {
                if ($this.val() == '') { $this.parents('.form-line').removeClass('focused'); }
            }
            else {
                $this.parents('.form-line').removeClass('focused');
            }
        });

        $parentSelector.on('click', '.form-float .form-line .form-label', function () {
            $(this).parent().find('input').focus();
        });

        $parentSelector.find('.form-control').each(function () {
            if ($(this).val() !== '') {
                $(this).parents('.form-line').addClass('focused');
            }
        });
    }
};

var edge = 'Microsoft Edge';
var ie10 = 'Internet Explorer 10';
var ie11 = 'Internet Explorer 11';
var opera = 'Opera';
var firefox = 'Mozilla Firefox';
var chrome = 'Google Chrome';
var safari = 'Safari';

$.VideoNotes.browser = {
    activate: function () {
        var _this = this;
        var className = _this.getClassName();

        if (className !== '') $('html').addClass(_this.getClassName());
    },
    getBrowser: function () {
        var userAgent = navigator.userAgent.toLowerCase();

        if (/edge/i.test(userAgent)) {
            return edge;
        } else if (/rv:11/i.test(userAgent)) {
            return ie11;
        } else if (/msie 10/i.test(userAgent)) {
            return ie10;
        } else if (/opr/i.test(userAgent)) {
            return opera;
        } else if (/chrome/i.test(userAgent)) {
            return chrome;
        } else if (/firefox/i.test(userAgent)) {
            return firefox;
        } else if (!!navigator.userAgent.match(/Version\/[\d\.]+.*Safari/)) {
            return safari;
        }

        return undefined;
    },
    getClassName: function () {
        var browser = this.getBrowser();

        if (browser === edge) {
            return 'edge';
        } else if (browser === ie11) {
            return 'ie11';
        } else if (browser === ie10) {
            return 'ie10';
        } else if (browser === opera) {
            return 'opera';
        } else if (browser === chrome) {
            return 'chrome';
        } else if (browser === firefox) {
            return 'firefox';
        } else if (browser === safari) {
            return 'safari';
        } else {
            return '';
        }
    }
}

$.VideoNotes.login = {
	activate: function () {
		$("#loginForm").on('submit',(function(e)
		{
			e.preventDefault();
			$('.loginButton').prop('disabled', true);
			$('.loginButton').html("Logging in...");
			$("#result").empty();
			$.ajax(
			{
				url: "login-a.php",
				type: "POST",
				data:  new FormData(this),
				contentType: false,
				cache: false,
				processData:false,
				headers : {
					'csrftoken': $('meta[name="csrf-token"]').attr('content')
				},
				success: function(data)
				{
					$("#loginForm").trigger("reset");
					setTimeout(function()
					{
						$('.loginButton').prop('disabled', false);
						$('.loginButton').html("Login");
						$('#username').focus();
						if(data == 1)
						{
							$("#result").html("<div class='alert alert-success'>You have successfully logged in. You are being redirected...</div>");
							setTimeout(function () { window.location.href = 'home'; }, 1000);
						}
						if(data == 2)
						{
							$("#result").html("<div class='alert alert-danger'>You entered incorrect information.</div>");
						}
                        if(data == 3)
                        {
                            $("#result").html("<div class='alert alert-danger'>You have exceeded the incorrect entry limit. Try again in half an hour.</div>");
                        }
						if(data == 4)
						{
							$("#result").html("<div class='alert alert-danger'>Please fill the form completely.</div>");
						}
					}, 1000);
				}	 						
			});
		}));
	}
};

$(function () {
    $.VideoNotes.browser.activate();
    $.VideoNotes.input.activate();
	$.VideoNotes.login.activate();
    setTimeout(function () { $('.page-loader-wrapper').fadeOut(); }, 50);
});
