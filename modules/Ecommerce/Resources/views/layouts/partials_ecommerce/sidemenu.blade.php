@php
    $tagid = Request::segment(3);

@endphp
<nav class="side-nav">
    <ul class="menu menu-vertical sf-arrows">
        <li class="{{ !$tagid ? 'active' : '' }}"><a href="{{ route('tenant.ecommerce.index') }}"><i
                    class="icon-home"></i>Home</a></li>
        {{-- <li>
              <a href="#" class="sf-with-ul"><i class="icon-briefcase"></i>
                  Categories</a>
              <div class="megamenu megamenu-fixed-width">
                  <div class="row">
                      <div class="col-lg-8">
                          <div class="row">
                              <div class="col-lg-6">
                                  <div class="menu-title">
                                      <a href="#">Electronica<span class="tip tip-new">New!</span></a>
                                  </div>
                                  <ul>
                                      <li><a href="#">Servidores<span
                                                  class="tip tip-hot">Hot!</span></a></li>
                                      <li><a href="#">Laptops</a></li>
                                      <li><a href="#">Mac Book Pro</a></li>
                                      <li><a href="#">Tablets</a></li>

                                  </ul>
                              </div><!-- End .col-lg-6 -->
                              <div class="col-lg-6">


                              </div><!-- End .col-lg-6 -->
                          </div><!-- End .row -->
                      </div><!-- End .col-lg-8 -->
                      <div class="col-lg-4">
                          <div class="banner">
                              <a href="#">
                                  <img src="{{ asset( 'porto-ecommerce/assets/images/menu-banner-2.jpg' ) }}" alt="Menu banner">
                              </a>
                          </div><!-- End .banner -->
                      </div><!-- End .col-lg-4 -->
                  </div>
              </div><!-- End .megamenu -->
          </li> --}}
        {{-- <li class="megamenu-container">
              <a href="{{route('tenant.ecommerce.item.index')}}" class="sf-with-ul"><i class="icon-video"></i>Products</a>
              <div class="megamenu">
                  <div class="row">
                      <div class="col-lg-8">
                          <div class="row">
                              <div class="col-lg-4">
                                  <div class="menu-title">
                                      <a href="#">Apple</a>
                                  </div>
                                  <ul>
                                      <li><a href="#">iMac</a></li>
                                      <li><a href="#">MacBook Pro<span
                                                  class="tip tip-hot">Hot!</span></a></li>
                                      <li><a href="#">Ipad 6</a></li>
                                      <li><a href="#">Addtocart Sticky</a></li>
                                      <li><a href="#">Accordion Tabs</a></li>
                                  </ul>
                              </div><!-- End .col-lg-4 -->
                              <div class="col-lg-4">
                                 <!-- <div class="menu-title">
                                      <a href="#">Variations</a>
                                  </div>
                                  <ul>
                                      <li><a href="product-sticky-tab.html">Sticky Tabs</a></li>
                                      <li><a href="product-simple.html">Simple Product</a></li>
                                      <li><a href="product-sidebar-left.html">With Left Sidebar</a></li>
                                  </ul> -->
                              </div><!-- End .col-lg-4 -->
                              <div class="col-lg-4">
                                  <!--<div class="menu-title">
                                      <a href="#">Product Layout Types</a>
                                  </div>
                                  <ul>
                                      <li><a href="product.html">Default Layout</a></li>
                                      <li><a href="product-extended-layout.html">Extended Layout</a></li>
                                      <li><a href="product-full-width.html">Full Width Layout</a></li>
                                      <li><a href="product-grid-layout.html">Grid Images Layout</a></li>
                                      <li><a href="product-sticky-both.html">Sticky Both Side Info<span
                                                  class="tip tip-hot">Hot!</span></a></li>
                                      <li><a href="product-sticky-info.html">Sticky Right Side Info</a></li>
                                  </ul> -->
                              </div><!-- End .col-lg-4 -->
                          </div><!-- End .row -->
                      </div><!-- End .col-lg-8 -->
                      <div class="col-lg-4">
                          <div class="banner">
                              <a href="#">
                                  <img src="{{ asset( 'porto-ecommerce/assets/images/menu-banner.jpg' ) }}" alt="Menu banner"
                                      class="product-promo">
                              </a>
                          </div><!-- End .banner -->
                      </div><!-- End .col-lg-4 -->
                  </div><!-- End .row -->
              </div><!-- End .megamenu -->
          </li> --}}
        {{-- <li>
              <a href="#" class="sf-with-ul"><i class="icon-docs-inv"></i>Pages</a>

              <ul>
                  <li><a href="cart.html">Shopping Cart</a></li>
                  <li><a href="#">Checkout</a>
                      <ul>
                          <li><a href="checkout-shipping.html">Checkout Shipping</a></li>
                          <li><a href="checkout-shipping-2.html">Checkout Shipping 2</a></li>
                          <li><a href="checkout-review.html">Checkout Review</a></li>
                      </ul>
                  </li>
                  <li><a href="#">Dashboard</a>
                      <ul>
                          <li><a href="dashboard.html">Dashboard</a></li>
                          <li><a href="my-account.html">My Account</a></li>
                      </ul>
                  </li>
                  <li><a href="about.html">About Us</a></li>
                  <li><a href="#">Blog</a>
                      <ul>
                          <li><a href="blog.html">Blog</a></li>
                          <li><a href="single.html">Blog Post</a></li>
                      </ul>
                  </li>
                  <li><a href="contact.html">Contact Us</a></li>
                  <li><a href="#" class="login-link">Login</a></li>
                  <li><a href="forgot-password.html">Forgot Password</a></li>
              </ul>
          </li> --}}
        {{-- <li><a href="#" class="sf-with-ul"><i class="icon-sliders"></i>Features</a>
              <ul>
                  <li><a href="#">Header Types</a></li>
                  <li><a href="#">Footer Types</a></li>
              </ul>
          </li> --}}

        @php
            $displayItems = $items->take(11);
            $hasMoreItems = $items->count() > 11;
            $hiddenItems = $items->slice(11);
        @endphp

        @foreach ($displayItems as $item)
            <li class="{{ $tagid == $item->id ? 'active' : '' }}">
                <a href="{{ route('tenant.ecommerce.category', ['category' => $item->id]) }}">
                    @if ($item->favicon)
                        <img src="{{ asset('storage/tags/' . $item->favicon) }}" alt="Logo" width="20" height="20"
                            style="display: inline-block; margin-right: 5px;" />
                    @else
                        <i class="icon-cat-gift"></i>
                    @endif
                    @php
                        echo wordwrap($item->name, 27, "<br />\n");
                    @endphp
                </a>
            </li>
        @endforeach
        @foreach ($hiddenItems as $item)
            <li class="{{ $tagid == $item->id ? 'active extra-item' : 'extra-item' }}" style="display: none;">
                <a href="{{ route('tenant.ecommerce.category', ['category' => $item->id]) }}">
                    @if ($item->favicon)
                    <img src="{{ asset('storage/tags/' . $item->favicon) }}" alt="Logo" width="20" height="20"
                        style="display: inline-block; margin-right: 5px;" />
                @else
                    <i class="icon-cat-gift"></i>
                @endif
                        @php
                            echo wordwrap($item->name, 27, "<br />\n");
                        @endphp
                </a>
            </li>
        @endforeach
        @if ($hasMoreItems)
            <li class="show-more-categories">
                <a href="#" id="show-more-categories">
                    MOSTRAR MÁS
                </a>
            </li>
        @endif

    </ul>
</nav>
@if ($hasMoreItems)
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#show-more-categories').click(function(event) {
                event.preventDefault();
                $('.extra-item').removeAttr('style');
                $(this).hide();
            });
        });
    </script>
@endif
</script>
