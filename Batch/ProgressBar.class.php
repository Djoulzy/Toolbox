<?php

function myEcho($str)
{
    echo $str;
    echo str_repeat(" ",1024*4);
}

class ProgressBar
{
    protected $largeur;
    
    public function __construct()
    {
$tmp = <<<EOT
        <div class="progress progress-striped active">
          <div id="url_gen_progress" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" />
        </div>

        <script>
        function setTick(value) {
            var bar = $('#url_gen_progress');
            var max = bar.attr('aria-valuemax');
            //var percentage = Math.round((value / max)*100);

            if (bar.width() == max) {
                $('.progress').removeClass('active');
            } else {
                bar.width(value+ "%");
            }
            bar.text(value + "%");
        }
        </script>
EOT;
        myEcho($tmp);
    }

    function update($now, $total)
    {
        $indice = round(($now / $total)*100);
        //vmeTraceLog('/data/VME/log/test.log', $now.' '.$total.' '.$indice);
        myEcho("\n<script>setTick(".$indice.")</script>");
    }
}
?> 