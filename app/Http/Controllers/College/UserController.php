<?php

namespace App\Http\Controllers\College;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ManagesUnitUsers;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    use ManagesUnitUsers;

    protected function unitRole(): string
    {
        return 'college_staff';
    }

    protected function unitPrefix(): string
    {
        return 'college';
    }

    protected function headTitle(): string
    {
        return 'Dean';
    }

    protected function scopeToUnit(Builder $query): Builder
    {
        $me = auth()->user();

        if ($me->college_id) {
            return $query->where('college_id', $me->college_id);
        }

        return $me->college_name
            ? $query->where('college_name', $me->college_name)
            : $query->whereKey($me->id); // no college set: only yourself
    }

    protected function unitAttributes(): array
    {
        $me = auth()->user();

        return ['college_id' => $me->college_id, 'college_name' => $me->college_name];
    }
}
