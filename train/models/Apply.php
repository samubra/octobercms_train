<?php namespace Samubra\Train\Models;

use Carbon\Carbon;
use October\Rain\Database\Pivot;
use ApplicationException;

class Apply extends Pivot
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;


    protected $dates = ['deleted_at'];
    protected $casts = [
        'remark' => 'array',
    ];
    protected $nullable = ['user_id','name','identity','edu_id','health_id','phone','address','company','status_id','pay','remark'];
    public $rules = [
        'is_review' => 'in:0,1,2',
        'name' => 'min:2',
        'identity' => 'identity',
        'edu_id' => 'required_with:name,identity,health_id,phone,address,company,status_id,pay|exists:samubra_train_lookup,id',
        'health_id' => 'required_with:name,identity,edu_id,phone,address,company,status_id,pay|exists:samubra_train_lookup,id',
        'phone' => 'telephone',
        'status_id' => 'required_with:name,identity,edu_id,health_id,phone,address,company,pay|exists:samubra_train_lookup,id',
        'pay' => 'numeric',
    ];
    public $belongsTo = [
        'health' => [Lookup::class,'key'=>'health_id','scope'=>'healthType'],
        'edu' => [Lookup::class,'key'=>'edu_id','scope'=>'eduType'],
        'apply_status' => [Lookup::class,'key'=>'status_id','scope'=>'applyStatus']
    ];


    protected $appends = ['health_name','eud_name','apply_status_name'];

    public function getIsReviewOptions()
    {
        return Plan::isReviewList();
    }

    public function getHealthNameAttribute()
    {
        return $this->health->name;
    }
    public function getEduNameAttribute()
    {
        return $this->edu->name;
    }
    public function getApplyStatusNameAttribute()
    {
        return $this->status_id ? $this->apply_status->name : '未填写';
    }

    public function beforeSave()
    {
        $planModel = Plan::findOrFail($this->plan_id);
        if(!$planModel->can_apply)
        {
            throw new ApplicationException('该培训计划已停止报名受理！');
            return false;
        }
        $recordModel = Record::findOrFail($this->record_id);

        if($planModel->type_id != $recordModel->type_id)
        {
            throw new ApplicationException('所选操作证的操作项目和该培训计划不一致，请重新选择添加！');
            return false;
        }

        if($planModel->is_review === 0)
        {
            if(!is_null($recordModel->first_get_date) || !is_null($recordModel->print_date) )
            {
                throw new ApplicationException('该培训计划为新训，不能选择该操作证，请重新选择添加！');
                return false;
            }

        }elseif ($planModel->is_review >= 1)
        {
            if(is_null($recordModel->first_get_date) || is_null($recordModel->print_date))
            {
                throw new ApplicationException('该培训计划为复训，但该操作证无发证，请重新选择添加！');
                return false;
            }

            //list($plan_start_year,$plan_start_month,$plan_start_day) = explode('-',$planModel->start_date);
            list($print_year,$print_month,$print_day) = explode('-',$recordModel->print_date);

            $planStartDate = Carbon::create();
            $recordPrintDate = Carbon::createFromDate($print_year,$print_month,$print_day);
            if($planModel->is_review === 1)
            {
                $startDate = $planStartDate->copy();
                $printDate = $recordPrintDate->copy();
                $reprintDate = $recordPrintDate->copy()->addYears(6);

                if(
                    !(($startDate->greaterThanOrEqualTo($printDate->addYears(3)->subMonths(2)) && $startDate->lessThanOrEqualTo($printDate->addMonths(2)->endOfMonth()) && !$recordModel->is_reviewed)
                    || ($startDate->greaterThanOrEqualTo($reprintDate->subMonths(2)) && $startDate->lessThanOrEqualTo($reprintDate->addMonths(2)) && $recordModel->is_reviewed))
                )
                {
                    //var_dump($reprintDate->toDateString());
                    throw new ApplicationException('该操作证不在允许的复审或换证日期范围内，请重新选择添加！');
                    return false;
                }
            }elseif(!($planStartDate->greaterThanOrEqualTo($recordPrintDate->addYears(6)->subMonths(2)) && $planStartDate->lessThanOrEqualTo($recordPrintDate->addMonths(2)) && $recordModel->is_reviewed)){
                throw new ApplicationException('该操作证不在允许的换证日期范围内，请重新选择添加！');
                return false;
            }
        }

    }

}