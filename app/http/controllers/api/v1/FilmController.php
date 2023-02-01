<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    public function index(){
        try {
            $films = Film::with(['category','directors','genres','actors','authors','producers','keyWords'])->latest()->get();
            return $this->_response('با موفقیت انجام شد', 1,[
                'films'=>$films
            ]);

        }catch (\Exception $e){
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -2,[]);
        }
    }

    public function filter(Request $request){
        try {
            $films = Film::with(['category','directors','actors','authors','producers','keyWords'])
                ->when(isset($request->categories_id), function ($query) use ($request) {
                    $query->where('films.category_id',$request->categories_id);
                })->when(isset($request->release_date_from), function ($query) use ($request) {
                    $query->where('films.release_date','>=',$request->release_date_from);
                })->when(isset($request->release_date_to), function ($query) use ($request) {
                    $query->where('films.release_date', '<=', $request->release_date_to);
                });
            if (isset($genres_id)){
                $films->whereHas('genres', function ($query) use ($request) {
                    $query->whereIn('genre_film.genre_id', $request->genres_id);
                });
            }else{
                $films->whereHas('genres');
            }
            $films=$films->latest()->get();
            return $this->_response('با موفقیت انجام شد', 1,[
                'films'=>$films
            ]);
        }catch (\Exception $e){
            return $this->_response('خطایی سمت سرور اتفاق افتاده است', -2,[]);
        }
    }


    private function _response(string $message, int $statusCode, array $data)
    {
        $response = [
            'message' => $message,
            'statusCode' => $statusCode,
        ];
        if (count($data) != 0) {
            $response = [
                'message' => $message,
                'statusCode' => $statusCode,
                'responseData' => $data,
            ];
        }
        return response()->json($response, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8']);
    }

}
