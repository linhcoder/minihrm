<?php
namespace App\Domain;
final class Problem extends \RuntimeException
{
    public function __construct(public int $status, string $message, public array $details = []) { parent::__construct($message); }
}
