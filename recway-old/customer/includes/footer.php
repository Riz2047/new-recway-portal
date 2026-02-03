</main>

</div>
<footer class="ms-auto  py-2 text-white text-center footer f-14 mt-3">
        Recway All rights reserved @<?= date("Y") ?>
</footer>

<div class="go-top">
        <a href="#top"><i class="bi bi-arrow-up"></i></a>
</div>




<div class="backdrop"></div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2"></script>
<script src="customer/assets/js/app.js"></script>
<script>
        function doGTranslate(lang_pair) {
                if (lang_pair.value) lang_pair = lang_pair.value;
                if (lang_pair == '') return;
                var lang = lang_pair.split('|')[1];
                var teCombo;
                var select = document.getElementsByClassName('goog-te-combo');
                for (var i = 0; i < select.length; i++)
                        if (select[i].className == 'goog-te-combo') teCombo = select[i];
                if (document.getElementById('google_translate_element2') == null || document.getElementById('google_translate_element2').innerHTML.length == 0 || teCombo.length == 0 || teCombo.innerHTML.length == 0) {
                        setTimeout(function() {
                                doGTranslate(lang_pair)
                        }, 500)
                } else {
                        teCombo.value = lang;
                        teCombo.dispatchEvent(new Event('change'));
                }
        }
        function googleTranslateElementInit2() {
                new google.translate.TranslateElement({
                        pageLanguage: 'en',
                        autoDisplay: false
                }, 'google_translate_element2');
        }
        document.addEventListener('DOMContentLoaded', function() {
                var lang_en = document.querySelector('#lang-en');
                var lang_sv = document.querySelector('#lang-sv');
                if (readCookie('googtrans') == undefined) {
                        lang_en.style.pointerEvents = 'none';
                        lang_en.classList.add('black-white');
                } else {
                        if (readCookie('googtrans') == '/en/sv') {
                                doGTranslate('sv')
                                lang_sv.style.pointerEvents = 'none';
                                lang_sv.classList.add('black-white')
                        } else {
                                doGTranslate('sv')
                                lang_en.style.pointerEvents = 'none';
                                lang_en.classList.add('black-white')
                        }
                }
                googleTranslateElementInit2()

        })
</script>
</body>

</html>