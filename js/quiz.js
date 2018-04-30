jQuery(document).ready(function($) {

    $("#post-body-content").prepend('<div id="quiz_error" class="error" style="display:none" ></div>');

    $('#post').submit(function() {

        if ( $("#post_type").val() =='wplm_quiz' ) {

            return wplm_validate_quizes();

        }

    });

    var wplm_validate_quizes = function() {

        var err = 0;

        $("#quiz_error").html("");

        $("#quiz_error").hide();

        if ( $("#title").val() == '' ) {

            $("#quiz_error").append("<p>Please enter Question Title.</p>");

            err++;

        }

        var correct_answer = $("#correct_answer").val();

        if ( $("#quiz_answer"+correct_answer).val() == "" ) {

            $("#quiz_error").append("<p>Correct answer cannot be empty.</p>");

            err++;

        }

        if ( err > 0 ) {

            $("#publish").removeClass("button-primary-disabled");

            $(".spinner").hide();

            $("#quiz_error").show();

            return false;

        }
        else {

            return true;

        }
    };

});
