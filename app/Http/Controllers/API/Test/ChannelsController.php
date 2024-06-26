<?php

namespace App\Http\Controllers\Api\General;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Repositories\ChannelRepository as MainRepository;
use Illuminate\Support\Str;

class ChannelsController  extends Controller
{
    protected $routeName = "api/general/channels/";
    protected $merchantPrefix = "pipay";
    protected $mainRepository;
    protected $balanceRepository;
    protected $merchantRepository;
  
    public function __construct(MainRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
   
    }
    public function index(Request $request)
    {
        return $this->mainRepository->getListItemsIsOnline();
    }
}
