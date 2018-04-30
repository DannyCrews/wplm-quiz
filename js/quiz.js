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

    $('#slider').rhinoslider( {
        controlsMousewheel: false,
        controlsPlayPause: false,
        showBullets: 'always',
        showControls: 'always'
    } );

    $("#completeQuiz").click(function() {

        wplm_quiz_results();

    });

    var wplm_quiz_results = function() {
    var selected_answers = {};
    $(".ques_answers").each(function() {
        var question_id = $(this).attr("data-quiz-id");
        var selected_answer = $(this).find('input[type=radio]:checked');
        if ( selected_answer.length != 0 ) {
            var selected_answer = $(selected_answer).val();
            selected_answers["qid_"+question_id] = selected_answer;
        }
        else {
            selected_answers["qid_"+question_id] = '';
        }
    } );
    // AJAX Request
    $.post(
        quiz.ajaxURL,
        {
        action: 'get_quiz_results',
        nonce: quiz.quizNonce,
        data: selected_answers
        },
        function(data) {
            // Section 1
            var total_questions = data.total_questions;
            $('#slider').data('rhinoslider').next($('#rhino-item' + total_questions));
            $('#score').html( data.score + '/' + total_questions);
            // Section 2
            var result_html = '<table>';
            result_html += '<tr><td>Question</td><td>Answer</td><td>Correct Answer</td><td>Result</td></tr>';
            var quiz_index = 1;
            $.each(data.result, function( key, ques ) {
                result_html += '<tr><td>' + quiz_index + '</td><td>' + ques.answer + '</td><td>' + ques.correct_answer + '</td>';
                result_html += '<td><img src="' + quiz.plugin_url + 'img/' + ques.mark + '.png" /></td></tr>';
                quiz_index++;
            });
            result_html += '<tr><td>&nbsp;</td><td></td><td></td>';
            result_html += '<td></td></tr>';
            // Section 3
            $('#quiz_result').parent().css('overflow-y','scroll');
            $('#quiz_result').html(result_html);
            $('#timer').hide();
            },
        'json'
        );
    };

    var duration = quiz.quizDuration * 60;

    $(document).ready(function($) {
        setTimeout("startPuzzleCount()",1000);
    });

    var startPuzzleCount = function() {
        duration--;
        $('#timer').html(duration+" Seconds Remaining");
        if ( duration == '0' ) {
            $('#timer').html("Time Up");
            wpq_quiz_results();
            return;
        }
        setTimeout("startPuzzleCount()",1000);
    };
});
