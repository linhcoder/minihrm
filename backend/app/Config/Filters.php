<?php
namespace Config;
class Filters extends \CodeIgniter\Config\Filters {
 public array $aliases=['secureheaders'=>\CodeIgniter\Filters\SecureHeaders::class];
 public array $required=['before'=>[],'after'=>[]];
 public array $globals=['before'=>[],'after'=>['secureheaders']];
 public array $methods=[];
 public array $filters=[];
}
