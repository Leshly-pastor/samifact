<?php

namespace Modules\BusinessTurn\Models;
 
use App\Models\Tenant\ModelTenant;
use App\Models\Tenant\Document;
use App\Models\Tenant\Catalogs\IdentityDocumentType;

class DocumentTransportDispatch extends ModelTenant
{   

    public $timestamps = false;
    protected $with = ['sender_identity_document_type', 'recipient_identity_document_type'];
    protected $table = 'document_transport_dispatches';
    protected $fillable = [
        'document_id',
        's_document_id',
        'sender_number_identity_document',
        'sender_passenger_fullname',
        'sender_telephone',
        'r_document_id',
        'recipient_number_identity_document',
        'recipient_passenger_fullname',
        'recipient_telephone',
    ];
  
 

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function sender_identity_document_type()
    {
        return $this->belongsTo(IdentityDocumentType::class, 's_document_id');
    }

    public function recipient_identity_document_type()
    {
        return $this->belongsTo(IdentityDocumentType::class, 'r_document_id');
    }

}