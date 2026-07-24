<?php
/**
 * Interactive HP <-> kW converter widget for Knowledge Center articles.
 * Self-contained (inline CSS + JS, no external deps). Drop the token
 * [[HP_KW_CALCULATOR]] into any article body to render it.
 */
if (!function_exists('getHpKwCalculator')) {
    function getHpKwCalculator()
    {
        ob_start(); ?>
<div class="hpkw-calc" id="hpkwCalc">
    <div class="hpkw-calc__head">
        <span class="hpkw-calc__icon" aria-hidden="true">&#9889;</span>
        <h3 class="hpkw-calc__title">HP &#8646; kW Converter</h3>
        <p class="hpkw-calc__sub">Type in either box &mdash; the other updates instantly.</p>
    </div>

    <div class="hpkw-calc__grid">
        <div class="hpkw-calc__field">
            <label for="hpkwHP">Horsepower (HP)</label>
            <div class="hpkw-calc__inputwrap">
                <input type="number" id="hpkwHP" inputmode="decimal" step="any" min="0" placeholder="0" value="1" aria-label="Horsepower">
                <span class="hpkw-calc__unit">HP</span>
            </div>
        </div>

        <div class="hpkw-calc__swap" aria-hidden="true">&#8646;</div>

        <div class="hpkw-calc__field">
            <label for="hpkwKW">Kilowatts (kW)</label>
            <div class="hpkw-calc__inputwrap">
                <input type="number" id="hpkwKW" inputmode="decimal" step="any" min="0" placeholder="0" aria-label="Kilowatts">
                <span class="hpkw-calc__unit">kW</span>
            </div>
        </div>
    </div>

    <div class="hpkw-calc__opts">
        <label for="hpkwType">HP standard:</label>
        <select id="hpkwType" aria-label="Horsepower standard">
            <option value="0.745699872" selected>Electric / Mechanical HP (1 HP = 0.7457 kW)</option>
            <option value="0.735498750">Metric HP &mdash; PS/CV (1 HP = 0.7355 kW)</option>
        </select>
    </div>

    <p class="hpkw-calc__result" id="hpkwResult"></p>

    <div class="hpkw-calc__quick">
        <span>Quick picks:</span>
        <button type="button" data-hp="1">1 HP</button>
        <button type="button" data-hp="2">2 HP</button>
        <button type="button" data-hp="3">3 HP</button>
        <button type="button" data-hp="5">5 HP</button>
        <button type="button" data-hp="7.5">7.5 HP</button>
        <button type="button" data-hp="10">10 HP</button>
        <button type="button" data-hp="15">15 HP</button>
        <button type="button" data-hp="20">20 HP</button>
    </div>
    <p class="hpkw-calc__note">Formula: kW = HP &times; conversion factor &nbsp;|&nbsp; HP = kW &divide; conversion factor. Electric motors in India are commonly rated using 1&nbsp;HP&nbsp;=&nbsp;0.746&nbsp;kW.</p>
</div>

<style>
.hpkw-calc{border:1px solid #e2e6ea;border-radius:12px;padding:24px;margin:28px 0;background:linear-gradient(180deg,#f7fbff 0%,#ffffff 60%);box-shadow:0 4px 18px rgba(21,123,186,.08);font-family:'Manrope',Arial,sans-serif;max-width:720px}
.hpkw-calc__head{text-align:center;margin-bottom:18px}
.hpkw-calc__icon{font-size:26px;color:#f5a623}
.hpkw-calc__title{font-family:'Libre Baskerville',serif;font-size:22px;color:#157bba;margin:4px 0 2px}
.hpkw-calc__sub{font-size:13px;color:#7a8288;margin:0}
.hpkw-calc__grid{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap}
.hpkw-calc__field{flex:1;min-width:200px}
.hpkw-calc__field label{display:block;font-size:13px;font-weight:600;color:#27252a;margin-bottom:6px}
.hpkw-calc__inputwrap{position:relative;display:flex;align-items:center}
.hpkw-calc__inputwrap input{width:100%;padding:14px 54px 14px 14px;font-size:22px;font-weight:700;color:#157bba;border:2px solid #d6dde3;border-radius:8px;background:#fff;transition:border-color .2s;-moz-appearance:textfield}
.hpkw-calc__inputwrap input:focus{outline:none;border-color:#157bba;box-shadow:0 0 0 3px rgba(21,123,186,.12)}
.hpkw-calc__unit{position:absolute;right:14px;font-size:14px;font-weight:600;color:#89868d;pointer-events:none}
.hpkw-calc__swap{font-size:26px;color:#157bba;padding-bottom:12px;flex:0 0 auto}
.hpkw-calc__opts{margin:16px 0 6px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.hpkw-calc__opts label{font-size:13px;font-weight:600;color:#27252a}
.hpkw-calc__opts select{flex:1;min-width:220px;padding:10px 12px;font-size:13px;border:2px solid #d6dde3;border-radius:8px;background:#fff;color:#27252a}
.hpkw-calc__opts select:focus{outline:none;border-color:#157bba}
.hpkw-calc__result{text-align:center;font-size:17px;color:#27252a;background:#eaf4fb;border-radius:8px;padding:12px;margin:14px 0 0;min-height:20px}
.hpkw-calc__result b{color:#157bba}
.hpkw-calc__quick{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:16px}
.hpkw-calc__quick span{font-size:13px;font-weight:600;color:#7a8288}
.hpkw-calc__quick button{border:1px solid #cfe0ec;background:#fff;color:#157bba;font-size:13px;font-weight:600;padding:6px 12px;border-radius:20px;cursor:pointer;transition:all .2s}
.hpkw-calc__quick button:hover{background:#157bba;color:#fff;border-color:#157bba}
.hpkw-calc__note{font-size:12px;color:#89868d;line-height:1.6;margin:16px 0 0}
@media (max-width:520px){.hpkw-calc__swap{display:none}.hpkw-calc__inputwrap input{font-size:19px}}
</style>

<script>
(function(){
    var hp=document.getElementById('hpkwHP'),kw=document.getElementById('hpkwKW'),
        type=document.getElementById('hpkwType'),res=document.getElementById('hpkwResult');
    function f(){return parseFloat(type.value);}
    function round(n){return (Math.round(n*10000)/10000).toString();}
    function say(){
        var h=parseFloat(hp.value),k=parseFloat(kw.value);
        if(!isNaN(h)&&h>=0){res.innerHTML='<b>'+round(h)+' HP</b> = <b>'+round(h*f())+' kW</b>';}
        else if(!isNaN(k)&&k>=0){res.innerHTML='<b>'+round(k)+' kW</b> = <b>'+round(k/f())+' HP</b>';}
        else{res.innerHTML='Enter a value above to convert.';}
    }
    function fromHP(){var h=parseFloat(hp.value);kw.value=(!isNaN(h)&&h>=0)?round(h*f()):'';say();}
    function fromKW(){var k=parseFloat(kw.value);hp.value=(!isNaN(k)&&k>=0)?round(k/f()):'';say();}
    hp.addEventListener('input',fromHP);
    kw.addEventListener('input',fromKW);
    type.addEventListener('change',fromHP);
    document.querySelectorAll('.hpkw-calc__quick button').forEach(function(b){
        b.addEventListener('click',function(){hp.value=b.getAttribute('data-hp');fromHP();hp.focus();});
    });
    fromHP();
})();
</script>
<?php
        return ob_get_clean();
    }
}
