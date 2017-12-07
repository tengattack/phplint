<?php

function formatter_json_render($results) {
  return json_encode($results, JSON_UNESCAPED_UNICODE);
}

return 'formatter_json_render';
