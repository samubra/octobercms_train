<?php namespace Samubra\Train\Models;

use Model;

/**
 * Model
 */
class Teacher extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'samubra_train_teacher';
    //protected $appends = ['type_name'];

    public $belongsTo = [
        'teacher_type' => [Lookup::class, 'key' => 'type_id','scope'=>'teacherType'],
        'edu_type' => [Lookup::class, 'key' => 'edu_id','scope'=>'eduType'],
    ];
    public $hasMany = [
        'courses' => [Course::class,'key' => 'teacher_id']
    ];

    public $attachOne = [
        'photo' => 'System\Models\File'
    ];


}