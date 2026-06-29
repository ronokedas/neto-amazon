<?php  
\$f = file_get_contents(" "\c:\sistema\modules\dashboard\index.php\');  
\$f = str_replace("\r\n\r\n        ^<^/a^>\r\n    ^<^/div^>\r\n^<^/div^>\r\n^<^?php endif; endif; ?>\r\n\r\nUltimas Vistorias^<^/h2^>", "\r\n", \$f);  
file_put_contents(\c:\sistema\modules\dashboard\index.php\', \$f); 
