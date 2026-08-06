<?php
namespace App\Support;
class FormTemplates {
    public static function all(): array { return [
        'contact_lead'=>['label'=>'Contact lead','defaults'=>['name'=>'Contact us','type'=>'lead','status'=>'draft','fields'=>[['key'=>'name','label'=>'Name','type'=>'text','required'=>true],['key'=>'email','label'=>'Email','type'=>'email','required'=>true],['key'=>'message','label'=>'Message','type'=>'textarea','required'=>false]],'settings'=>[],'thank_you'=>'Thanks! We will be in touch.']],
        'nps_score'=>['label'=>'NPS score','defaults'=>['name'=>'How did we do?','type'=>'nps','status'=>'draft','fields'=>[['key'=>'score','label'=>'How likely are you to recommend us?','type'=>'nps','required'=>true],['key'=>'feedback','label'=>'What can we improve?','type'=>'textarea','required'=>false]],'settings'=>[],'thank_you'=>'Thank you for your feedback.']],
        'product_quiz'=>['label'=>'Product quiz','defaults'=>['name'=>'Find your fit','type'=>'quiz','status'=>'draft','fields'=>[['key'=>'use_case','label'=>'What do you need?','type'=>'select','required'=>true,'options'=>['Personal','Business']],['key'=>'email','label'=>'Email','type'=>'email','required'=>true]],'settings'=>[],'thank_you'=>'Your result is on its way.']],
    ]; }
    public static function get(string $key): ?array { return self::all()[$key] ?? null; }
}
