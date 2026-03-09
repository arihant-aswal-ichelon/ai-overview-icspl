<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedQuestions extends Model
{
    use HasFactory;
    protected $table = 'related_questions';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'keyword_planner_id',
        'cluster_request_id',
        'history_log_id',
        'priority',
        'question',
        'answer',
        'source_title',
        'source_link',
        'source_source',
        'source_domain',
        'source_displayed_link',
        'source_favicon',
        'json',
        'date',
        'created_at',
        'updated_at',
        
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('priority', function (Builder $builder) {
            $builder->where(function (Builder $query) {
                // Scenario 1: history_log_id is NULL → only show priority = 1
                $query->where(function (Builder $q) {
                    $q->whereNull('history_log_id')
                    ->where('priority', 1);
                })
                // Scenario 2: history_log_id is NOT NULL
                ->orWhereNotNull('history_log_id');
            });


            // Apply GROUP BY only when history_log_id is passed as a concrete value
            $hasHistoryLogId = collect($builder->getQuery()->wheres)
                ->contains(fn($where) =>
                    ($where['column'] ?? '') === 'history_log_id' &&
                    ($where['type'] ?? '') === 'Basic'
                );

            if ($hasHistoryLogId) {
                $builder->groupBy('question');
            }
        });
    }
}
