// Standard license block omitted.
/*
 * @package    block_overview
 * @copyright  2015 Someone cool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module block_search_books/checkbox
  */
define(['jquery', 'block_search_books/checkbox'], function($) {

    return {
        init: function() {
            $(document).ready(function() {

                $('#checkholder').hide();

                $('#check').change(function(){
                    if($('#check').is(":checked")){
                        $('.checkbox1').each(function() {
                            this.checked = true;
                        });
                    }else{
                        $('.checkbox1').each(function() {
                            this.checked = false;
                        });
                    }
                });

                $('#toggle').click(function(e){
                    e.stopImmediatePropagation();

                    window.console.log(e);
                    if( $('#checkholder').css('display') == 'none' ){
                        $('#checkholder').show();
                    }else{
                        $('#checkholder').hide();
                    }
                });

            });
        }
    };

});