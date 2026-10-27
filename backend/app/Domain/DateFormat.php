<?php
namespace App\Domain;
/** ISO/UTC stays on the wire; Vietnamese wall-clock formatting belongs at presentation boundaries. */
final class DateFormat
{
    public const TIMEZONE='Asia/Ho_Chi_Minh';
    public static function date(string $value): string
    {
        $format=preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$value)?'d/m/Y':'Y-m-d';
        $date=\DateTimeImmutable::createFromFormat('!'.$format,$value);
        if(!$date||$date->format($format)!==$value)throw new Problem(422,'Ngày không hợp lệ. Sử dụng dd/mm/yyyy.');
        return $date->format('Y-m-d');
    }
    public static function time(string $value): string
    {
        if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',$value))throw new Problem(422,'Giờ không hợp lệ. Sử dụng HH:mm:ss.');
        return strlen($value)===5?$value.':00':$value;
    }
    public static function instant(string $value,string $timezone=self::TIMEZONE): string
    {
        if(preg_match('/^\d{2}\/\d{2}\/\d{4} /',$value)){
            $date=\DateTimeImmutable::createFromFormat('!d/m/Y H:i:s',$value,new \DateTimeZone($timezone));
            if(!$date||$date->format('d/m/Y H:i:s')!==$value)throw new Problem(422,'Ngày giờ không hợp lệ. Sử dụng dd/mm/yyyy HH:mm:ss.');
        }else{
            if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',$value))throw new Problem(422,'Ngày giờ không hợp lệ. Sử dụng dd/mm/yyyy HH:mm:ss.');
            self::date(substr($value,0,10));$date=new \DateTimeImmutable($value);
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
    public static function display(mixed $value): mixed
    {
        if(is_array($value))return array_map([self::class,'display'],$value);
        if(!is_string($value))return $value;
        if(str_starts_with($value,'{')||str_starts_with($value,'[')){$decoded=json_decode($value,true);if(json_last_error()===JSON_ERROR_NONE&&is_array($decoded))return json_encode(self::display($decoded),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$value))return (new \DateTimeImmutable(self::date($value)))->format('d/m/Y');
        if(preg_match('/^\d{2}:\d{2}(?::\d{2})?$/',$value))return self::time($value);
        if(preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/',$value))return (new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('d/m/Y H:i:s');
        return $value;
    }
    public static function boundary(string $date,bool $end=false): string{return (new \DateTimeImmutable(self::date($date).($end?' 23:59:59':' 00:00:00'),new \DateTimeZone(self::TIMEZONE)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');}
}
