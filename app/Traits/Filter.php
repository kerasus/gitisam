<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

trait Filter
{
    private function filterByRelationId(Request $request, $filterData, & $modelQuery) {
        $requestKey = $filterData['requestKey'];
        $relationName = (isset($filterData['relationName'])) ? $filterData['relationName'] : null;
        $relationNames = (isset($filterData['relationNames'])) ? $filterData['relationNames'] : null;
        $orWhereHas = (isset($filterData['orWhereHas'])) ? $filterData['orWhereHas'] : false;

        $relationIds = $request->get($requestKey);
        if (!isset($relationIds)) {
            return;
        }
        if (is_array($relationIds) && count($relationIds) === 0) {
            return;
        }
        if ($orWhereHas && is_array($relationNames) && count($relationNames) === 0) {
            return;
        }

        if (!is_array($relationNames)) {
            $relationNames = [$relationNames];
        }
        if (!is_array($relationIds)) {
            $relationIds = [$relationIds];
        }

        if ($orWhereHas) {
            foreach ($relationNames as $relationNameItem) {
                $modelQuery->orWhereHas($relationNameItem, function (Builder $query) use ($relationIds) {
                    $tableName = with($query)->getModel()->getTable();
                    $query->whereIn($tableName.'.id', $relationIds);
                });
            }
        } else {
            $modelQuery->whereHas($relationName, function (Builder $query) use ($relationIds) {
                $tableName = with($query)->getModel()->getTable();
                $query->whereIn($tableName.'.id', $relationIds);
            });
        }
    }

    private function filterByRelationKey(Request $request, $filterData, & $modelQuery) {

        $requestKey = $filterData['requestKey'];
        $relationName = (isset($filterData['relationName'])) ? $filterData['relationName'] : null;
        $relationColumn = (isset($filterData['relationColumn'])) ? $filterData['relationColumn'] : null;

        $name = $request->get($requestKey);
        if (!isset($name)) {
            return;
        }
        $modelQuery->whereHas($relationName, function (Builder $query) use ($name, $relationColumn) {
            $query->where($relationColumn, 'like', '%' . $name . '%');
        });
    }

    private function filterByKey($request, $key, & $modelQuery) {
        $keyValue = trim($request->get($key));
        if (isset($keyValue) && strlen($keyValue) > 0) {
            $modelQuery = $modelQuery->where($key, 'like', '%' . $keyValue . '%');
        }
    }

    private function filterByKeyExact($request, $key, & $modelQuery) {
        $keyValue = trim($request->get($key));
        if (isset($keyValue) && strlen($keyValue) > 0) {
            $modelQuery = $modelQuery->where($key, '=', $keyValue);
        }
    }

    private function filterByKeyIn($request, $key, & $modelQuery) {
        $keyValue = trim($request->get($key . '_in'));
        if (isset($keyValue) && is_array($keyValue)) {
            $modelQuery = $modelQuery->whereIn($key, $keyValue);
        }
    }

    private function filterByDate($request, & $modelQuery, &$filterDate) {

        $filterDate []= 'created_at';

        foreach ($filterDate as $ke=>$value) {

            if ($value === 'created_at') {
                $sinceDateKey = 'createdSinceDate';
                $tillDateKey = 'createdTillDate';
            } else {
                $sinceDateKey = $value.'_since_date';
                $tillDateKey = $value.'_till_date';
            }

            $sinceDate  = $request->get($sinceDateKey);
            $tillDate   = $request->get($tillDateKey);
            if (strlen($sinceDate) > 0 && strlen($tillDate) > 0) {
                $sinceDate  = Carbon::parse($sinceDate)->format('Y-m-d H:m:s');
                $tillDate   = Carbon::parse($tillDate)->format('Y-m-d H:m:s');
                $modelQuery = $modelQuery->whereBetween($value, [$sinceDate, $tillDate]);
            } else if (strlen($sinceDate) > 0) {
                $sinceDate  = Carbon::parse($sinceDate)->format('Y-m-d H:m:s');
                $modelQuery = $modelQuery->where($value, '>=', $sinceDate);
            } else if (strlen($tillDate) > 0) {
                $tillDate   = Carbon::parse($tillDate)->format('Y-m-d H:m:s');
                $modelQuery = $modelQuery->where($value, '<=', $tillDate);
            }
        }

//        $createdSinceDate  = $request->get('createdSinceDate');
//        $createdTillDate   = $request->get('createdTillDate');
//        if (strlen($createdSinceDate) > 0 && strlen($createdTillDate) > 0) {
//            $createdSinceDate = Carbon::parse($createdSinceDate)->format('Y-m-d H:m:s');
//            $createdTillDate  = Carbon::parse($createdTillDate)->format('Y-m-d H:m:s');
//            $modelQuery       = $modelQuery->whereBetween('created_at', [$createdSinceDate, $createdTillDate]);
//        } else if (strlen($createdSinceDate) > 0) {
//            $createdSinceDate = Carbon::parse($createdSinceDate)->format('Y-m-d H:m:s');
//            $modelQuery       = $modelQuery->where('created_at', '>=', $createdSinceDate);
//        } else if (strlen($createdTillDate) > 0) {
//            $createdTillDate  = Carbon::parse($createdTillDate)->format('Y-m-d H:m:s');
//            $modelQuery       = $modelQuery->where('created_at', '<=', $createdTillDate);
//        }
    }

    /**
     * @param $response
     * @return ResponseFactory|Response
     */
    private function jsonResponseOk($response) {
        return response(json_encode($response), Response::HTTP_OK)->header('Content-Type', 'application/json');
    }

    private function jsonResponseValidateError($response) {
        return response(json_encode($response), Response::HTTP_UNPROCESSABLE_ENTITY)->header('Content-Type', 'application/json');
    }

    private function jsonResponseServerError($response) {
        return response(json_encode($response), Response::HTTP_INTERNAL_SERVER_ERROR)->header('Content-Type', 'application/json');
    }

    private function checkOwner ($userOwnerId) {
        if (!Auth::user()->hasRole('Super Admin') && Auth::user()->id !== (int)$userOwnerId) {
            abort(403, 'Access denied');
        }
    }
}
