<?php

namespace App\Http\Utils;


use Exception;

use Illuminate\Database\Eloquent\Builder;

trait DefaultQueryScopesTrait
{

    public function scopeForSuperAdmin(Builder $query, $table)
    {
        return $query->where($table . '.business_id', NULL)
                     ->where($table . '.is_default', 1)
                     ->when(isset(request()->is_active), function ($query) use ($table) {
                         return $query->where($table . '.is_active', request()->boolean('is_active'));
                     });
    }


    public function scopeForNonSuperAdmin(Builder $query, $table, $disabled_table, $created_by)
    {
        return $query->where(function ($query) use ($table, $created_by, $disabled_table) {
            $query->where($table . '.business_id', NULL)
                  ->where($table . '.is_default', 1)
                  ->where($table . '.is_active', 1)
                  ->when(request()->has('is_active'), function ($query) use ($created_by, $disabled_table) {
                      if (request()->boolean('is_active')) {
                          return $query->whereDoesntHave('disabled', function ($q) use ($created_by, $disabled_table) {
                              $q->whereIn($disabled_table . '.created_by', [$created_by]);
                          });
                      }
                  })
                  ->orWhere(function ($query) use ($table, $created_by) {
                      $query->where($table . '.business_id', NULL)
                            ->where($table . '.is_default', 0)
                            ->where($table . '.created_by', $created_by)
                            ->when(request()->has('is_active'), function ($query) use ($table) {
                                return $query->where($table . '.is_active', request()->boolean('is_active'));
                            });
                  });
        });
    }


    public function scopeForBusiness(Builder $query, $table,$disabled_table, $created_by)
    {
        return $query->where(function ($query) use ($table, $created_by, $disabled_table) {
            $query->when(!request()->boolean('exclude_defaults'), function ($query) use ($table, $created_by, $disabled_table) {
                return $query->where(function ($query) use ($table, $created_by, $disabled_table) {
                    $query->where($table . '.business_id', NULL)
                          ->where($table . '.is_default', 1)
                          ->where($table . '.is_active', 1)
                          ->whereDoesntHave('disabled', function ($q) use ($created_by, $disabled_table) {
                              $q->whereIn($disabled_table . '.created_by', [$created_by]);
                          })
                          ->when(request()->has('is_active'), function ($query) use ($disabled_table) {
                              if (request()->boolean('is_active')) {
                                  return $query->whereDoesntHave('disabled', function ($q) use ($disabled_table) {
                                      $q->whereIn($disabled_table . '.business_id', [auth()->user()->business_id]);
                                  });
                              }
                          })
                          ->orWhere(function ($query) use ($table,$disabled_table, $created_by) {
                              $query->where($table . '.business_id', NULL)
                                    ->where($table . '.is_default', 0)
                                    ->where($table . '.created_by', $created_by)
                                    ->where($table . '.is_active', 1)
                                    ->when(request()->has('is_active'), function ($query) use ($disabled_table) {
                                        if (request()->boolean('is_active')) {
                                            return $query->whereDoesntHave('disabled', function ($q) use ($disabled_table) {
                                                $q->whereIn($disabled_table . '.business_id', [auth()->user()->business_id]);
                                            });
                                        }
                                    });
                          });
                });
            })
            ->orWhere(function ($query) use ($table) {
                $query->where($table . '.business_id', auth()->user()->business_id)
                      ->where($table . '.is_default', 0)
                      ->when(request()->boolean('is_active'), function ($query) use ($table) {
                          return $query->where($table . '.is_active', 1);
                      });
            });
        });
    }






}
