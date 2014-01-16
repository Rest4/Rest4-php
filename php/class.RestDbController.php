<?php
class RestDbController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Serve database contents.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    $request->database='';
    $request->table='';
    $request->entry='';
    if ($request->uriNodes->count()>4) {
      throw new RestException(RestCodes::HTTP_400,
                              'Too many nodes in that uri.');
    }
    if (isset($request->uriNodes[1])&&$request->uriNodes[1]) {
      $request->database=$request->uriNodes[1];
      if (isset($request->uriNodes[2])&&$request->uriNodes[2]) {
        $request->table=$request->uriNodes[2];
        if (isset($request->uriNodes[3])&&$request->uriNodes[3]!=='') {
          $request->entry=$request->uriNodes[3];
        }
      }
    }
    // Reject folders
    if ($request->isFolder) {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for this ressource.',
        '',
        array('Location'=>'/db'
          .($request->database?'/'.$request->database:'')
          .($request->table?'/'.$request->table:'')
          .($request->entry?'/'.$request->entry:'')
          .($request->fileExt?'.'.$request->fileExt:'.dat')
          .($request->queryString?'?'.$request->queryString:'')));
    }
    // Lauching the good driver
    if (ctype_digit($request->entry)) {
      $driver=new RestDbEntryDriver($request);
    } elseif ($request->entry=='list') {
      $driver=new RestDbEntriesDriver($request);
    } elseif ($request->entry=='tree') {
      $driver=new RestDbTreeDriver($request);
    } elseif ($request->entry=='import') {
      $driver=new RestDbTableImportDriver($request);
    } elseif ($request->entry!=='') {
      throw new RestException(RestCodes::HTTP_400,
        'Can\'t interpret entry node in that uri ('.$request->entry.')');
    } elseif ($request->table) {
      $driver=new RestDbTableDriver($request);
    } elseif ($request->database) {
      $driver=new RestDbBaseDriver($request);
    } else {
      $driver=new RestDbServerDriver($request);
    }
    parent::__construct($driver);
  }
}

