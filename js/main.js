! function (t) {
    "use strict";
    var e;
    if (t(window).on("load", (function () {
            t(".preloader").fadeOut(), t(".slick-slider").slick("refresh")
        })), t(window).on("resize", (function () {
            t(".slick-slider").slick("refresh")
        })), t(".preloader").length > 0 && t(".preloaderCls").each((function () {
            t(this).on("click", (function (e) {
                e.preventDefault(), t(".preloader").css("display", "none")
            }))
        })), t.fn.thmobilemenu = function (e) {
            var a = t.extend({
                menuToggleBtn: ".th-menu-toggle",
                bodyToggleClass: "th-body-visible",
                subMenuClass: "th-submenu",
                subMenuParent: "th-item-has-children",
                subMenuParentToggle: "th-active",
                meanExpandClass: "th-mean-expand",
                appendElement: '<span class="th-mean-expand"></span>',
                subMenuToggleClass: "th-open",
                toggleSpeed: 400
            }, e);
            return this.each((function () {
                var e = t(this);

                function s() {
                    e.toggleClass(a.bodyToggleClass);
                    var s = "." + a.subMenuClass;
                    t(s).each((function () {
                        t(this).hasClass(a.subMenuToggleClass) && (t(this).removeClass(a.subMenuToggleClass), t(this).css("display", "none"), t(this).parent().removeClass(a.subMenuParentToggle))
                    }))
                }
                e.find("li").each((function () {
                    var e = t(this).find("ul");
                    e.addClass(a.subMenuClass), e.css("display", "none"), e.parent().addClass(a.subMenuParent), e.prev("a").append(a.appendElement), e.next("a").append(a.appendElement)
                }));
                var i = "." + a.meanExpandClass;
                t(i).each((function () {
                    t(this).on("click", (function (e) {
                        var s;
                        e.preventDefault(), s = t(this).parent(), t(s).next("ul").length > 0 ? (t(s).parent().toggleClass(a.subMenuParentToggle), t(s).next("ul").slideToggle(a.toggleSpeed), t(s).next("ul").toggleClass(a.subMenuToggleClass)) : t(s).prev("ul").length > 0 && (t(s).parent().toggleClass(a.subMenuParentToggle), t(s).prev("ul").slideToggle(a.toggleSpeed), t(s).prev("ul").toggleClass(a.subMenuToggleClass))
                    }))
                })), t(a.menuToggleBtn).each((function () {
                    t(this).on("click", (function () {
                        s()
                    }))
                })), e.on("click", (function (t) {
                    t.stopPropagation(), s()
                })), e.find("div").on("click", (function (t) {
                    t.stopPropagation()
                }))
            }))
        }, t(".th-menu-wrapper").thmobilemenu(), t(window).scroll((function () {
            t(this).scrollTop() > 500 ? t(".sticky-wrapper").addClass("sticky") : t(".sticky-wrapper").removeClass("sticky")
        })), t(e = ".onepage-nav").length > 0 && t(e).each((function () {
            var e = t(this).find("a");
            t(this).find(e).each((function () {
                t(this).on("click", (function () {
                    var e = t(this.getAttribute("href"));
                    e.length && (event.preventDefault(), t("html, body").stop().animate({
                        scrollTop: e.offset().top - 10
                    }, 1e3))
                }))
            }))
        })), t(".scroll-top").length > 0) {
        var a = document.querySelector(".scroll-top"),
            s = document.querySelector(".scroll-top path"),
            i = s.getTotalLength();
        s.style.transition = s.style.WebkitTransition = "none", s.style.strokeDasharray = i + " " + i, s.style.strokeDashoffset = i, s.getBoundingClientRect(), s.style.transition = s.style.WebkitTransition = "stroke-dashoffset 10ms linear";
        var n = function () {
            var e = t(window).scrollTop(),
                a = t(document).height() - t(window).height(),
                n = i - e * i / a;
            s.style.strokeDashoffset = n
        };
        n(), t(window).scroll(n);
        jQuery(window).on("scroll", (function () {
            jQuery(this).scrollTop() > 50 ? jQuery(a).addClass("show") : jQuery(a).removeClass("show")
        })), jQuery(a).on("click", (function (t) {
            return t.preventDefault(), jQuery("html, body").animate({
                scrollTop: 0
            }, 750), !1
        }))
    }
    t("[data-bg-src]").length > 0 && t("[data-bg-src]").each((function () {
        var e = t(this).attr("data-bg-src");
        t(this).css("background-image", "url(" + e + ")"), t(this).removeAttr("data-bg-src").addClass("background-image")
    })), t("[data-bg-color]").length > 0 && t("[data-bg-color]").each((function () {
        var e = t(this).attr("data-bg-color");
        t(this).css("background-color", e), t(this).removeAttr("data-bg-color")
    })), t("[data-mask-src]").length > 0 && t("[data-mask-src]").each((function () {
        var e = t(this).attr("data-mask-src");
        t(this).css({
            "mask-image": "url(" + e + ")",
            "-webkit-mask-image": "url(" + e + ")"
        }), t(this).addClass("bg-mask"), t(this).removeAttr("data-mask-src")
    })), t("[data-theme-color]").length > 0 && t("[data-theme-color]").each((function () {
        var e = t(this).attr("data-theme-color");
        t(this).get(0).style.setProperty("--theme-color", e), t(this).removeAttr("data-theme-color")
    })), t("[data-body-color]").length > 0 && t("[data-body-color]").each((function () {
        var e = t(this).attr("data-body-color");
        t(this).get(0).style.setProperty("--body-color", e), t(this).removeAttr("data-body-color")
    })), t(".th-carousel").each((function () {
        var e = t(this);

        function a(t) {
            return e.data(t)
        }
        var s = '<button type="button" class="slick-prev"><i class="' + a("prev-arrow") + '"></i></button>',
            i = '<button type="button" class="slick-next"><i class="' + a("next-arrow") + '"></i></button>';
        t("[data-slick-next]").each((function () {
            t(this).on("click", (function (e) {
                e.preventDefault(), t(t(this).data("slick-next")).slick("slickNext")
            }))
        })), t("[data-slick-prev]").each((function () {
            t(this).on("click", (function (e) {
                e.preventDefault(), t(t(this).data("slick-prev")).slick("slickPrev")
            }))
        })), 1 == a("arrows") && (e.closest(".arrow-wrap").length || e.closest(".container").parent().addClass("arrow-wrap")), e.slick({
            dots: !!a("dots"),
            fade: !!a("fade"),
            arrows: !!a("arrows"),
            speed: a("speed") ? a("speed") : 1e3,
            asNavFor: !!a("asnavfor") && a("asnavfor"),
            autoplay: 0 != a("autoplay"),
            infinite: 0 != a("infinite"),
            slidesToShow: a("slide-show") ? a("slide-show") : 1,
            adaptiveHeight: !!a("adaptive-height"),
            centerMode: !!a("center-mode"),
            autoplaySpeed: a("autoplay-speed") ? a("autoplay-speed") : 8e3,
            centerPadding: a("center-padding") ? a("center-padding") : "0",
            focusOnSelect: 0 != a("focuson-select"),
            pauseOnFocus: !!a("pauseon-focus"),
            pauseOnHover: !!a("pauseon-hover"),
            variableWidth: !!a("variable-width"),
            vertical: !!a("vertical"),
            verticalSwiping: !!a("vertical"),
            swipeToSlide: !!a("swipetoslide"),
            prevArrow: a("prev-arrow") ? s : '<button type="button" class="slick-prev"><i class="far fa-arrow-left"></i></button>',
            nextArrow: a("next-arrow") ? i : '<button type="button" class="slick-next"><i class="far fa-arrow-right"></i></button>',
            rtl: "rtl" == t("html").attr("dir"),
            responsive: [{
                breakpoint: 1600,
                settings: {
                    arrows: !!a("xl-arrows"),
                    dots: !!a("xl-dots"),
                    slidesToShow: a("xl-slide-show") ? a("xl-slide-show") : a("slide-show"),
                    centerMode: !!a("xl-center-mode"),
                    centerPadding: "0"
                }
            }, {
                breakpoint: 1400,
                settings: {
                    arrows: !!a("ml-arrows"),
                    dots: !!a("ml-dots"),
                    slidesToShow: a("ml-slide-show") ? a("ml-slide-show") : a("slide-show"),
                    centerMode: !!a("ml-center-mode"),
                    centerPadding: 0
                }
            }, {
                breakpoint: 1200,
                settings: {
                    arrows: !!a("lg-arrows"),
                    dots: !!a("lg-dots"),
                    slidesToShow: a("lg-slide-show") ? a("lg-slide-show") : a("slide-show"),
                    centerMode: !!a("lg-center-mode") && a("lg-center-mode"),
                    centerPadding: 0
                }
            }, {
                breakpoint: 992,
                settings: {
                    arrows: !!a("md-arrows"),
                    dots: !!a("md-dots"),
                    slidesToShow: a("md-slide-show") ? a("md-slide-show") : 1,
                    centerMode: !!a("md-center-mode") && a("md-center-mode"),
                    centerPadding: 0
                }
            }, {
                breakpoint: 768,
                settings: {
                    arrows: !!a("sm-arrows"),
                    dots: !!a("sm-dots"),
                    slidesToShow: a("sm-slide-show") ? a("sm-slide-show") : 1,
                    centerMode: !!a("sm-center-mode") && a("sm-center-mode"),
                    centerPadding: 0
                }
            }, {
                breakpoint: 576,
                settings: {
                    arrows: !!a("xs-arrows"),
                    dots: !!a("xs-dots"),
                    slidesToShow: a("xs-slide-show") ? a("xs-slide-show") : 1,
                    centerMode: !!a("xs-center-mode") && a("xs-center-mode"),
                    centerPadding: 0
                }
            }]
        })
    })), t("[data-ani-duration]").each((function () {
        var e = t(this).data("ani-duration");
        t(this).css("animation-duration", e)
    })), t("[data-ani-delay]").each((function () {
        var e = t(this).data("ani-delay");
        t(this).css("animation-delay", e)
    })), t("[data-ani]").each((function () {
        var e = t(this).data("ani");
        t(this).addClass(e), t(".slick-current [data-ani]").addClass("th-animated")
    })), t(".th-carousel").on("afterChange", (function (e, a, s, i) {
        t(a.$slides).find("[data-ani]").removeClass("th-animated"), t(a.$slides[s]).find("[data-ani]").addClass("th-animated")
    }));
    var o, r, c, l = ".ajax-contact",
        d = '[name="email"]',
        h = t(".form-messages");

    function p() {
        var e = t(l).serialize();
        (function () {
            var e, a = !0;

            function s(s) {
                s = s.split(",");
                for (var i = 0; i < s.length; i++) e = l + " " + s[i], t(e).val() ? (t(e).removeClass("is-invalid"), a = !0) : (t(e).addClass("is-invalid"), a = !1)
            }
            s('[name="name"],[name="email"],[name="subject"],[name="number"],[name="message"]'), t(d).val() && t(d).val().match(/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/) ? (t(d).removeClass("is-invalid"), a = !0) : (t(d).addClass("is-invalid"), a = !1);
            return a
        })() && jQuery.ajax({
            url: t(l).attr("action"),
            data: e,
            type: "POST"
        }).done((function (e) {
            h.removeClass("error"), h.addClass("success"), h.text(e), t(l + ' input:not([type="submit"]),' + l + " textarea").val("")
        })).fail((function (t) {
            h.removeClass("success"), h.addClass("error"), "" !== t.responseText ? h.html(t.responseText) : h.html("Oops! An error occured and your message could not be sent.")
        }))
    }

    function u(t) {
        return parseInt(t, 10)
    }
    t(l).on("submit", (function (t) {
            t.preventDefault(), p()
        })), o = ".popup-search-box", r = ".searchClose", c = "show", t(".searchBoxToggler").on("click", (function (e) {
            e.preventDefault(), t(o).addClass(c)
        })), t(o).on("click", (function (e) {
            e.stopPropagation(), t(o).removeClass(c)
        })), t(o).find("form").on("click", (function (e) {
            e.stopPropagation(), t(o).addClass(c)
        })), t(r).on("click", (function (e) {
            e.preventDefault(), e.stopPropagation(), t(o).removeClass(c)
        })),
        function (e, a, s, i) {
            t(a).on("click", (function (a) {
                a.preventDefault(), t(e).addClass(i)
            })), t(e).on("click", (function (a) {
                a.stopPropagation(), t(e).removeClass(i)
            })), t(e + " > div").on("click", (function (a) {
                a.stopPropagation(), t(e).addClass(i)
            })), t(s).on("click", (function (a) {
                a.preventDefault(), a.stopPropagation(), t(e).removeClass(i)
            }))
        }(".sidemenu-wrapper", ".sideMenuToggler", ".sideMenuCls", "show"), t(".popup-image").magnificPopup({
            type: "image",
            mainClass: "mfp-zoom-in",
            removalDelay: 260,
            gallery: {
                enabled: !0
            }
        }), t(".popup-video").magnificPopup({
            type: "iframe",
            mainClass: "mfp-zoom-in",
            removalDelay: 260
        }), t(".popup-content").magnificPopup({
            type: "inline",
            midClick: !0
        }), t(".popup-content").on("click", (function () {
            t(".slick-slider").slick("refresh")
        })), t.fn.sectionPosition = function (e, a) {
            t(this).each((function () {
                var s, i, n, o, r, c = t(this);
                s = Math.floor(c.height() / 2), i = c.attr(e), n = c.attr(a), o = u(t(n).css("padding-top")), r = u(t(n).css("padding-bottom")), "top-half" === i ? (t(n).css("padding-bottom", r + s + "px"), c.css("margin-top", "-" + s + "px")) : "bottom-half" === i && (t(n).css("padding-top", o + s + "px"), c.css("margin-bottom", "-" + s + "px"))
            }))
        };

    function f() {
        t(".feature-circle .progressbar").each((function () {
            var e = t(this).attr("data-path-color"),
                a = t(this).offset().top,
                s = t(window).scrollTop(),
                i = t(this).find(".circle").attr("data-percent"),
                n = (parseInt(i, 10), parseInt(100, 10), t(this).data("animate"));
            a < s + t(window).height() - 30 && !n && (t(this).data("animate", !0), t(this).find(".circle").circleProgress({
                startAngle: -Math.PI / 2,
                value: i / 100,
                size: 100,
                thickness: 6,
                emptyFill: "transparent",
                lineCap: "round",
                fill: {
                    color: e
                }
            }).on("circle-animation-progress", (function (e, a, s) {
                t(this).find(".circle-num").text((100 * s).toFixed(0) + "%")
            })).stop())
        })), t(".skill-circle .progressbar").each((function () {
            var e = t(this).offset().top,
                a = t(window).scrollTop(),
                s = t(this).find(".circle").attr("data-percent"),
                i = (parseInt(s, 10), parseInt(100, 10), t(this).data("animate"));
            e < a + t(window).height() - 30 && !i && (t(this).data("animate", !0), t(this).find(".circle").circleProgress({
                startAngle: -Math.PI / 2,
                value: s / 100,
                size: 100,
                thickness: 8,
                emptyFill: "#E0E0E0",
                lineCap: "round",
                fill: {
                    gradient: ["#F11F22", "#F2891D"]
                }
            }).on("circle-animation-progress", (function (e, a, s) {
                t(this).find(".circle-num").text((100 * s).toFixed(0) + "%")
            })).stop())
        }))
    }
    t("[data-sec-pos]").length && t("[data-sec-pos]").imagesLoaded((function () {
        t("[data-sec-pos]").sectionPosition("data-sec-pos", "data-pos-for")
    })), t(".filter-active").imagesLoaded((function () {
        if (t(".filter-active").length > 0) {
            var e = t(".filter-active").isotope({
                itemSelector: ".filter-item",
                filter: "*",
                masonry: {
                    columnWidth: 1
                }
            });
            t(".filter-menu-active").on("click", "button", (function () {
                var a = t(this).attr("data-filter");
                e.isotope({
                    filter: a
                })
            })), t(".filter-menu-active").on("click", "button", (function (e) {
                e.preventDefault(), t(this).addClass("active"), t(this).siblings(".active").removeClass("active")
            }))
        }
    })), t(".masonary-active").imagesLoaded((function () {
        t(".masonary-active").length > 0 && t(".masonary-active").isotope({
            itemSelector: ".filter-item",
            filter: "*",
            masonry: {
                columnWidth: 1
            }
        })
    })), t(".masonary-active, .woocommerce-Reviews .comment-list").imagesLoaded((function () {
        var e = ".masonary-active, .woocommerce-Reviews .comment-list";
        t(e).length > 0 && t(e).isotope({
            itemSelector: ".filter-item, .woocommerce-Reviews .comment-list li",
            filter: "*",
            masonry: {
                columnWidth: 1
            }
        }), t('[data-bs-toggle="tab"]').on("shown.bs.tab", (function (a) {
            t(e).isotope({
                filter: "*"
            })
        }))
    })), t(".counter-number").counterUp({
        delay: 10,
        time: 1e3
    }), t.fn.thTab = function (e) {
        var a = t.extend({
            sliderTab: !1,
            tabButton: "button"
        }, e);
        t(this).each((function () {
            var e = t(this),
                s = e.find(a.tabButton);
            e.append('<span class="indicator"></span>');
            var i = e.find(".indicator");
            if (s.on("click", (function (e) {
                    e.preventDefault();
                    var s = t(this);
                    s.addClass("active").siblings().removeClass("active"), a.sliderTab ? t(n).slick("slickGoTo", s.data("slide-go-to")) : r()
                })), a.sliderTab) {
                var n = e.data("asnavfor"),
                    o = 0;
                s.each((function () {
                    var s = t(this);
                    s.attr("data-slide-go-to", o), o++, s.hasClass("active") && t(n).slick("slickGoTo", s.data("slide-go-to")), t(n).on("beforeChange", (function (t, s, i, n) {
                        e.find(a.tabButton + '[data-slide-go-to="' + n + '"]').addClass("active").siblings().removeClass("active"), r()
                    }))
                }))
            }

            function r() {
                var n = e.find(a.tabButton + ".active"),
                    o = n.css("height"),
                    r = n.css("width"),
                    c = n.position().top + "px",
                    l = n.position().left + "px";
                i.get(0).style.setProperty("--height-set", o), i.get(0).style.setProperty("--width-set", r), i.get(0).style.setProperty("--pos-y", c), i.get(0).style.setProperty("--pos-x", l), t(s).first().position().left == n.position().left ? i.addClass("start").removeClass("center").removeClass("end") : t(s).last().position().left == n.position().left ? i.addClass("end").removeClass("center").removeClass("start") : i.addClass("center").removeClass("start").removeClass("end")
            }
            r()
        }))
    }, t(".testi-card2-tab").length && t(".testi-card2-tab").thTab({
        sliderTab: !0,
        tabButton: ".tab-btn"
    }), t(".service-block-tab").length && t(".service-block-tab").thTab({
        sliderTab: !0,
        tabButton: ".tab-btn"
    }), t.fn.shapeMockup = function () {
        t(this).each((function () {
            var e = t(this),
                a = e.data("top"),
                s = e.data("right"),
                i = e.data("bottom"),
                n = e.data("left");
            e.css({
                top: a,
                right: s,
                bottom: i,
                left: n
            }).removeAttr("data-top").removeAttr("data-right").removeAttr("data-bottom").removeAttr("data-left").parent().addClass("shape-mockup-wrap")
        }))
    }, t(".shape-mockup") && t(".shape-mockup").shapeMockup(), t(".progress-bar").waypoint((function () {
        t(".progress-bar").css({
            animation: "animate-positive 1.8s",
            opacity: "1"
        })
    }), {
        offset: "75%"
    }), t(".price_slider").slider({
        range: !0,
        min: 10,
        max: 100,
        values: [10, 75],
        slide: function (e, a) {
            t(".from").text("$" + a.values[0]), t(".to").text("$" + a.values[1])
        }
    }), t(".from").text("$" + t(".price_slider").slider("values", 0)), t(".to").text("$" + t(".price_slider").slider("values", 1)), t(".tilt-active").tilt({
        maxTilt: 7,
        perspective: 1e3
    }), t.fn.indicator = function () {
        var e = t(this),
            a = e.find("a"),
            s = e.find("button");
        e.append('<span class="indicator"></span>');
        var i = e.find(".indicator");
        if (a.length) var n = a;
        else if (s.length) n = s;

        function o() {
            var a = e.find(".active"),
                s = a.css("height"),
                n = a.css("width"),
                o = a.position().top + "px",
                r = a.position().left + "px";
            t(window).on("resize", (function () {
                o = a.position().top + "px", r = a.position().left + "px"
            })), i.get(0).style.setProperty("--height-set", s), i.get(0).style.setProperty("--width-set", n), i.get(0).style.setProperty("--pos-y", o), i.get(0).style.setProperty("--pos-x", r)
        }
        n.on("click", (function (e) {
            e.preventDefault(), t(this).addClass("active"), t(this).siblings(".active").removeClass("active"), o()
        })), o()
    }, t(".indicator-active").length && t(".indicator-active").indicator(), f(), t(window).scroll(f), t("#ship-to-different-address-checkbox").on("change", (function () {
        t(this).is(":checked") ? t("#ship-to-different-address").next(".shipping_address").slideDown() : t("#ship-to-different-address").next(".shipping_address").slideUp()
    })), t(".woocommerce-form-login-toggle a").on("click", (function (e) {
        e.preventDefault(), t(".woocommerce-form-login").slideToggle()
    })), t(".woocommerce-form-coupon-toggle a").on("click", (function (e) {
        e.preventDefault(), t(".woocommerce-form-coupon").slideToggle()
    })), t(".shipping-calculator-button").on("click", (function (e) {
        e.preventDefault(), t(this).next(".shipping-calculator-form").slideToggle()
    })), t('.wc_payment_methods input[type="radio"]:checked').siblings(".payment_box").show(), t('.wc_payment_methods input[type="radio"]').each((function () {
        t(this).on("change", (function () {
            t(".payment_box").slideUp(), t(this).siblings(".payment_box").slideDown()
        }))
    })), t(".rating-select .stars a").each((function () {
        t(this).on("click", (function (e) {
            e.preventDefault(), t(this).siblings().removeClass("active"), t(this).parent().parent().addClass("selected"), t(this).addClass("active")
        }))
    }));
    const m = {};

    function g() {
        const e = t(this),
            a = e.attr("src");
        if (!m[a]) {
            const e = t.Deferred();
            t.get(a, a => {
                e.resolve(t(a).find("svg"))
            }), m[a] = e.promise()
        }
        m[a].then(a => {
            const s = t(a).clone();
            e.attr("id") && s.attr("id", e.attr("id")), e.attr("class") && s.attr("class", e.attr("class")), e.attr("style") && s.attr("style", e.attr("style")), e.attr("width") && (s.attr("width", e.attr("width")), e.attr("height") || s.removeAttr("height")), e.attr("height") && (s.attr("height", e.attr("height")), e.attr("width") || s.removeAttr("width")), s.insertAfter(e), e.trigger("svgInlined", s[0]), e.remove()
        })
    }

    function v(e, a, s, i) {
        var n = e.text().split(a),
            o = "";
        n.length && (t(n).each((function (t, e) {
            o += '<span class="' + s + (t + 1) + '">' + e + "</span>" + i
        })), e.empty().append(o))
    }
    t.fn.inlineSvg = function () {
        return this.each(g), this
    }, t(".svg-img").inlineSvg(), t(".quantity-plus").each((function () {
        t(this).on("click", (function (e) {
            e.preventDefault();
            var a = t(this).siblings(".qty-input"),
                s = parseInt(a.val(), 10);
            isNaN(s) || a.val(s + 1)
        }))
    })), t(".quantity-minus").each((function () {
        t(this).on("click", (function (e) {
            e.preventDefault();
            var a = t(this).siblings(".qty-input"),
                s = parseInt(a.val(), 10);
            !isNaN(s) && s > 1 && a.val(s - 1)
        }))
    })), t("#testiSlide1").slick({
        centerMode: !0,
        centerPadding: "600px",
        slidesToShow: 1,
        arrows: !1,
        dots: !0,
        responsive: [{
            breakpoint: 1800,
            settings: {
                centerPadding: "300px"
            }
        }, {
            breakpoint: 1199,
            settings: {
                centerPadding: "200px"
            }
        }, {
            breakpoint: 992,
            settings: {
                centerPadding: "60px"
            }
        }, {
            breakpoint: 600,
            settings: {
                centerPadding: "0px"
            }
        }]
    }), t(document).on("mouseover", ".team-card-1-1", (function () {
        t(this).addClass("team-card-1-1-active"), t(".team-card-1-1").removeClass("team-card-1-1-active"), t(this).addClass("team-card-1-1-active")
    })), t(document).on("mouseover", ".team-card-1-2", (function () {
        t(this).addClass("team-card-1-2-active"), t(".team-card-1-2").removeClass("team-card-1-2-active"), t(this).addClass("team-card-1-2-active")
    })), t(document).on("mouseover", ".service-card2", (function () {
        t(this).addClass("service-card2-active"), t(".service-card2").removeClass("service-card2-active"), t(this).addClass("service-card2-active")
    })), t(document).on("mouseover", ".cat-card2", (function () {
        t(this).addClass("cat-card2-active"), t(".cat-card2").removeClass("cat-card2-active"), t(this).addClass("cat-card2-active")
    }));
    var b = {
        init: function () {
            return this.each((function () {
                v(t(this), "", "char", "")
            }))
        },
        words: function () {
            return this.each((function () {
                v(t(this), " ", "word", " ")
            }))
        },
        lines: function () {
            return this.each((function () {
                var e = "eefec303079ad17405c889e092e105b0";
                v(t(this).children("br").replaceWith(e).end(), e, "line", "")
            }))
        }
    };
    t.fn.lettering = function (e) {
        return e && b[e] ? b[e].apply(this, [].slice.call(arguments, 1)) : "letters" !== e && e ? (t.error("Method " + e + " does not exist on jQuery.lettering"), this) : b.init.apply(this, [].slice.call(arguments, 0))
    }, t(".about-title-anime").lettering(), t.fn.countdown = function () {
        t(this).each((function () {
            var e = t(this),
                a = new Date(e.data("offer-date")).getTime();

            function s(t) {
                return e.find(t)
            }
            var i = setInterval((function () {
                var t = (new Date).getTime(),
                    n = a - t,
                    o = Math.floor(n / 864e5),
                    r = Math.floor(n % 864e5 / 36e5),
                    c = Math.floor(n % 36e5 / 6e4),
                    l = Math.floor(n % 6e4 / 1e3);
                o < 10 && (o = "0" + o), r < 10 && (r = "0" + r), c < 10 && (c = "0" + c), l < 10 && (l = "0" + l), n < 0 ? (clearInterval(i), e.addClass("expired"), e.find(".message").css("display", "block")) : (s(".day").html(o), s(".hour").html(r), s(".minute").html(c), s(".seconds").html(l))
            }), 1e3)
        }))
    }, t(".counter-list").length && t(".counter-list").countdown(), t(".single-select").length && t(".single-select").niceSelect(), window.addEventListener("contextmenu", (function (t) {
        t.preventDefault()
    }), !1), document.onkeydown = function (t) {
        return 123 != event.keyCode && ((!t.ctrlKey || !t.shiftKey || t.keyCode != "I".charCodeAt(0)) && ((!t.ctrlKey || !t.shiftKey || t.keyCode != "C".charCodeAt(0)) && ((!t.ctrlKey || !t.shiftKey || t.keyCode != "J".charCodeAt(0)) && ((!t.ctrlKey || t.keyCode != "U".charCodeAt(0)) && void 0))))
    }
}(jQuery);