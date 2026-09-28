@php
  $enabled=\App\Models\Setting::valueOf('purchase_notice_enabled','1')==='1';
  $scope=\App\Models\Setting::valueOf('purchase_notice_scope','all');
  $route=request()->route()?->getName();
  $visible=$enabled && ($scope==='all' || ($scope==='product' && in_array($route,['home','products.show'],true)) || ($scope==='checkout' && in_array($route,['cart.index','orders.show'],true)));
  $orders=$visible ? \App\Models\Order::query()->with(['items','product'])->where('status','paid')->whereNotNull('paid_at')->orderByDesc('paid_at')->limit(5)->get() : collect();
  $template=\App\Models\Setting::valueOf('purchase_notice_text','Seorang pembeli telah berhasil membeli {produk} senilai {harga}.');
@endphp
@if($orders->isNotEmpty())
  <aside class="purchase-toast" id="purchase-toast" aria-label="Pembelian terbaru" role="status" aria-live="polite" hidden>
    <span class="purchase-toast-icon" aria-hidden="true">✓</span>
    <div class="purchase-toast-messages">
      @foreach($orders as $paidOrder)
        @php
          $item=$paidOrder->items->first();
          $productName=$item?->product_name ?? $paidOrder->product?->name ?? 'produk pilihan';
          if($paidOrder->items->count()>1)$productName.=' dan '.($paidOrder->items->count()-1).' produk lainnya';
          $message=strtr($template,['{produk}'=>$productName,'{harga}'=>'Rp '.number_format($paidOrder->total,0,',','.')]);
        @endphp
        <p class="purchase-toast-message" @if(!$loop->first) hidden @endif>{{ $message }}</p>
      @endforeach
    </div>
    <button type="button" class="purchase-toast-close" aria-label="Tutup notifikasi pembelian">×</button>
  </aside>
  <script>
    (() => {
      const toast=document.getElementById('purchase-toast');
      const messages=[...toast.querySelectorAll('.purchase-toast-message')];
      let index=0,closed=false;
      const show=()=>{
        if(closed)return;
        messages.forEach((message,i)=>message.hidden=i!==index);
        index=(index+1)%messages.length;
        toast.hidden=false;
        setTimeout(()=>{toast.hidden=true},6500);
      };
      const start=setTimeout(show,1200);
      const interval=setInterval(show,15000);
      toast.querySelector('.purchase-toast-close').addEventListener('click',()=>{
        closed=true;
        toast.hidden=true;
        clearTimeout(start);
        clearInterval(interval);
      });
    })();
  </script>
@endif
