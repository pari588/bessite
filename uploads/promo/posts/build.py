#!/usr/bin/env python3
"""
BES social post batch builder.

  python3 build.py            build every post
  python3 build.py <slug>     build one

Each post gets its own folder with a generated template.html, conformed
artwork in out/, and a caption file. The review page index.html lists them all.

BRAND RULE — enforced in code below, not left to memory: a motor post carries
the CG monogram, a pump post carries the Crompton wordmark. CG Power and
Crompton are separate companies. build() refuses to run if a post's `kind`
and brand logo disagree.
"""
import os, re, html, subprocess, shutil, sys

HERE  = os.path.dirname(os.path.abspath(__file__))
CHROME = os.path.expanduser("~/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome")

BRAND = {
    'motor': ('cg-motors.png',      'CG Power', 46, 'Motors on ex-stock basis'),
    'pump':  ('crompton-pumps.png', 'Crompton', 26, 'Pumps on ex-stock basis'),
}

POSTS = [
# ─────────────────────────── MOTORS ───────────────────────────
{
 'slug':'hazardous-area', 'kind':'motor',
 'title':'Hazardous Area Motors',
 # supplied by the client from cgglobal.com; prepared copy sits in this post's assets/
 'src':'uploads/promo/posts/hazardous-area/assets/cg-flameproof.jpg',
 'img_alt':'CG flameproof hazardous area motor',
 'badge':'Hazardous area',
 # "Where safety is not an option" would read as safety being unavailable — the
 # meaning intended is that it is mandatory, so: "not optional".
 'headline':'Where safety<br><em>is not optional.</em>',
 'subline':'Flameproof, Increased Safety and Non-Sparking motors for Zone 1 &amp; Zone 2 '
           'for Refineries, Chemical and Pharma Plants, Paint Shops.',
 'plate_title':'Hazardous area motors', 'plate_ref':'Ex-stock &amp; built to order',
 'rows':[('Protection','Ex &ldquo;db&rdquo; · Ex &ldquo;eb&rdquo; · Ex &ldquo;ec&rdquo;',1),
         ('Gas groups','IIA / IIB · IIC',1),
         ('Output','0.37 kW to 535 kW',1),
         ('Voltage','415 V · others on request',1),
         ('Certification','PESO · ATEX · IECEx',1)],
 'note':'Genuine spares available',
 'cta':'Tell us the zone and gas group',
 'hl':56, 'hl_story':68, 'shot':430, 'shot_story':690, 'key_w':205, 'val':28,
 'caption':"""Where safety is not optional.

In a Hazardous area the motor is not just a machine, it is part of the safety case. Choose the right motor for the right kind of Hazardous area. Use our expertise to select the right motor.

We supply the Hazardous area motors:
For Zone 1 and 2: Flameproof Ex "db"
For Zone 2: Increased Safety Ex "eb" and Non-Sparking Ex "ec"

⚙️ Protection — Ex "db", Ex "eb", Ex "ec"
🧪 Gas groups — IIA / IIB, IIC
⚡ Output — 0.37 kW to 535 kW
🔌 Voltage — 415 V, other voltages on request
📋 Certification — PESO, ATEX, IECEx

Built for Refineries, Chemical Plants, Pharmaceutical Plants and Paint Shops — anywhere a flammable atmosphere is a normal part of the working day.

🔧 Genuine spares available.

Please specify your requirement: Application, Zone, Gas Group, Temp Class, Motor driven by VFD or DOL start and run. We will select and offer the best motor for the given application.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#FlameProofMotor #HazardousAreaMotors #ExdMotor #ATEX #IECEx #PESO #ExplosionProof #Zone1 #Zone2 #RefinerySafety #ChemicalPlant #PharmaManufacturing #PaintShop #ProcessIndustry #IndustrialMotors #ElectricMotor #PlantEngineering #Mumbai #Ahmedabad #IndustrialSupplies""",
},
{
 'slug':'fire-pump-motors', 'kind':'motor',
 'title':'UL Listed Fire Pump Motors',
 'src':'uploads/motor/fire-fighting-ul-listed-motors.webp',
 'img_alt':'UL Listed fire pump motor',
 'badge':'Fire fighting',
 'headline':'The motor you hope<br><em>never runs.</em>',
 'subline':'UL Listed fire pump motors, built and certified for the one day '
           'everything depends on them starting.',
 'plate_title':'Fire pump motors', 'plate_ref':'UL Listed',
 'rows':[('Listing','UL 1004-5 · NFPA 20',1),
         ('Output','5 HP to 500 HP',1),
         ('Supply','415 V to 6.6 kV',1),
         ('Frames','NEMA 184T to 580',1)],
 'cta':'Ask us for the UL listing certificate',
 'hl':60, 'hl_story':72, 'shot':512, 'shot_story':760, 'key_w':190, 'val':31,
 'caption':"""The motor you hope never runs.

A fire pump motor spends its whole life doing nothing. That is exactly why the certification matters more here than anywhere else in the plant — there is no gradual failure to warn you, and no second attempt.

UL Listed to UL 1004-5 and built to NFPA 20, so the listing holds up when the consultant, the insurer or the fire officer asks for it.

⚙️ Listing — UL 1004-5, NFPA 20
⚡ Output — 5 HP to 500 HP
🔌 Supply — 415 V to 6.6 kV
📐 Frames — NEMA 184T to 580

Specifying for a new building or replacing an existing set? Send us the pump curve and the NOC requirement and we will match the motor to it.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#FirePumpMotor #FireFighting #NFPA20 #ULListed #FireSafety #FireProtection #SprinklerSystem #IndustrialMotors #ElectricMotor #BuildingServices #MEPEngineering #PlantEngineering #FactoryMaintenance #Mumbai #Ahmedabad #IndustrialSupplies #ManufacturingIndia""",
},
{
 'slug':'read-the-nameplate', 'kind':'motor',
 'title':'How to Read a Motor Nameplate',
 # client-supplied plate, Jul 2026 — reads 'CG Power and Industrial Solutions Ltd'
 # (the previous one showed the pre-2016 'Crompton Greaves Ltd' on a CG post)
 # client-supplied, already cropped to the plate with the frame intact — used as is
 'src':'uploads/promo/posts/read-the-nameplate/assets/nameplate.jpg',
 'img_alt':'Motor rating plate',
 'badge':'',            # plate fills the frame; the headline already names it
 'headline':'Everything we need<br><em>is on this plate.</em>',
 'subline':'One clear photo of the rating plate and we can size a replacement '
           'without visiting your site. Here is what we actually read.',
 'plate_title':'What we read', 'plate_ref':'And why',
 'rows':[('Frame / kW','Mounting and duty',0),
         ('Volts / Amps','Your supply',0),
         ('RPM / Poles','The speed',0),
         ('IP / Class','The environment',0)],
 'cta':'WhatsApp the nameplate to 98200 42210',
 'hl':58, 'hl_story':70, 'shot':520, 'shot_story':760, 'key_w':215, 'val':29,
 'caption':"""Everything we need is on this plate.

Most enquiries start with "I need a 5 HP motor." That is rarely enough. Two motors of the same rating can be completely different machines.

Here is what we actually read, and why it matters:

⚙️ FRAME and kW — the frame decides whether it bolts onto your existing foundation. Get this wrong and you are drilling a new bed.
⚡ VOLTS and AMPS — confirms your supply, and the current tells us whether the old motor was working harder than it should have been.
🔄 RPM and POLES — 1440 and 2880 rpm are not interchangeable. The driven load was chosen for one of them.
🛡️ IP and INSULATION CLASS — IP55 in a wash-down bay, IP66 outdoors, Class F where it runs hot. This is the number people skip and then wonder why the replacement failed early.
📅 The date — a motor made before 2012 is almost certainly below IE2. Replacing it is usually an efficiency upgrade whether you intended one or not.

So: photograph the plate, straight on, in good light. Send it to us. We will tell you what fits, what has changed since it was made, and what drops into the same foundation.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#MotorNameplate #IndustrialMotors #ElectricMotor #MotorSizing #FactoryMaintenance #PlantEngineering #MaintenanceTips #EngineeringBasics #IE3Motors #EnergyEfficiency #MotorReplacement #Mumbai #Ahmedabad #IndustrialSupplies #ManufacturingIndia""",
},
{
 'slug':'forced-cooling', 'kind':'motor', 'photo':True,
 'title':'Forced Cooling on VFD Drives',
 'src':'uploads/promo/posts/forced-cooling/assets/hero.jpg',
 'img_alt':'Motor fitted with an independent forced-cooling blower',
 'badge':'Know your motor',
 'headline':'The motor slows.<br><em>So does its fan.</em>',
 'subline':'On a VFD in constant-torque duty the losses stay put. The cooling does not. '
           'Past a certain output, forced ventilation stops being optional.',
 'plate_title':'Cooling on a drive', 'plate_ref':'IEC 60034-6',
 'rows':[('Standard fit','IC411 · fan on the shaft',1),
         ('Forced cooling','IC416 · independent blower',1),
         ('Airflow','Constant at any speed',0),
         ('Result','Full torque to near zero',0)],
 'note':'We size the blower with the motor',
 'cta':'Tell us the speed range and the duty',
 'hl':56, 'hl_story':68, 'shot':500, 'shot_story':780, 'key_w':230, 'val':28,
 'caption':"""The motor slows. So does its fan.

A standard TEFC motor is IC411 — the cooling fan sits on the motor's own shaft. Slow the motor down and the fan slows with it. On a pump or a fan load that is fine, because the torque demand falls away with speed too.

On constant-torque duty it is not fine.

A Conveyor, an Extruder, a Mixer, a Hoist or a positive-displacement pump needs full torque at every speed. Full torque means near-full current, and near-full current means the motor keeps making close to its full heat — while the shaft-mounted fan is delivering a fraction of the air it was designed for. The heat stays. The cooling leaves.

Run it that way continuously and the winding temperature climbs. A self-cooled motor on constant-torque duty usually has to be derated once it runs below roughly half its base speed. Where exactly depends on the frame and the manufacturer's curve — it is not a single number.

Forced cooling, IC416 under IEC 60034-6, puts the fan on its own small motor running at constant speed no matter what the main motor is doing. Full airflow at 5 Hz and at 50 Hz alike, so the motor holds its torque rating from near zero to base speed without a derate.

Why it matters more at higher outputs: a bigger frame has less surface area for every kilowatt of loss it must shed, and the absolute heat is larger. What a small motor shrugs off, a big one does not.

⚙️ Standard fit — IC411, fan on the motor shaft
🌬️ Forced cooling — IC416, independent blower
📏 Airflow — constant at any speed
✅ Result — full torque from near zero to base speed

Look at your nameplate. If it reads IC411 and that motor is on a drive doing constant-torque work at low speed, it is worth a conversation.

Send us the speed range, the duty and the output, and we will tell you whether you need forced cooling or a derate.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#IC416 #ForcedCooling #MotorCooling #VFD #VariableFrequencyDrive #ConstantTorque #IndustrialMotors #ElectricMotor #IEC60034 #MotorDerating #Conveyor #Extruder #PlantEngineering #FactoryMaintenance #Mumbai #Ahmedabad #IndustrialSupplies""",
},
# ─────────────────────────── PUMPS ───────────────────────────
{
 'slug':'sewage-drainage', 'kind':'pump',
 'title':'Sewage & Drainage Pumps',
 'src':'uploads/pump/530_530_crop_100/stpm22-1ph-15.webp',
 'img_alt':'Submersible sewage and drainage pump',
 'badge':'Sewage &amp; drainage',
 'headline':'When the basement fills,<br><em>an ordinary pump chokes.</em>',
 'subline':'Submersible sewage and drainage pumps built to pass solids '
           'instead of jamming on them.',
 'plate_title':'Sewage &amp; drainage', 'plate_ref':'Ex-stock',
 'rows':[('Handles','Effluent · solids · dirty water',0),
         ('Built for','Basements · Societies · Industry',0),
         ('Supply','Single and three phase',0),
         ('Backup','Genuine spares',0)],
 'cta':'Tell us the sump depth and discharge',
 'hl':50, 'hl_story':60, 'shot':512, 'shot_story':760, 'key_w':195, 'val':27,
 'caption':"""When the basement fills, an ordinary pump chokes.

Every monsoon it is the same call: the sump pump has stopped, and when it comes out there is a rag, a wipe or a lump of silt wrapped around the impeller. A clean-water pump was never going to survive that duty.

Sewage and drainage pumps are built differently — wider passages, cutter or vortex impellers, and mechanical seals meant for dirty water. They pass what would jam an ordinary pump.

⚙️ Handles — effluent, solids, dirty water
🏢 Built for — Basements, Housing Societies, Commercial Buildings, Industry
⚡ Supply — single and three phase
🔧 Backup — genuine spares held in stock

Sizing one properly needs the sump depth, the discharge height, the pipe size and an honest description of what is going into it. Tell us those and we will get it right the first time.

Standby matters too. A single pump in a basement is a single point of failure on the worst night of the year.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#SewagePump #DrainagePump #Dewatering #SubmersiblePump #Monsoon #Waterlogging #BasementFlooding #STP #ETP #WastewaterPumping #HousingSociety #BuildingServices #FacilityManagement #Mumbai #Ahmedabad #IndustrialSupplies""",
},
{
 'slug':'dewatering-pumps', 'kind':'pump', 'photo':True,
 'title':'Dewatering Pumps',
 'src':'uploads/promo/posts/dewatering-pumps/assets/hero.jpg',
 'img_alt':'Submersible dewatering pump clearing a flooded floor',
 'badge':'Monsoon &mdash; dewatering',
 'headline':'Every hour it stands,<br><em>it costs you.</em>',
 'subline':'Submersible dewatering pumps for flooded Basements, Pits, Compounds '
           'and Shop Floors &mdash; ex-stock through the monsoon.',
 'plate_title':'Dewatering pumps', 'plate_ref':'Ex-stock',
 'rows':[('Handles','Rainwater · Seepage · Muddy Water',0),
         ('Built for','Basements · Pits · Compounds',0),
         ('Supply','Single and Three Phase',0)],
 'note':'Genuine spares available',
 'cta':'Tell us the depth and the discharge height',
 'hl':54, 'hl_story':66, 'shot':560, 'shot_story':860, 'key_w':200, 'val':28,
 'caption':"""Every hour it stands, it costs you.

Monsoon water in a Basement, a Pit or a Compound is never just water. It is stock, motors, panels and production sitting in it, and the bill grows with every hour it stays.

Worth knowing before you buy, because it is the mistake we see most: a dewatering pump and a sewage pump are not the same machine. Dewatering pumps shift rainwater, seepage and muddy water quickly. Sewage pumps use vortex or cutter impellers and wide passages to pass solids and effluent. Put a dewatering pump on a sewage sump and it jams. Put a sewage pump on a clear-water job and you have paid for passages you will never use.

⚙️ Handles — rainwater, seepage, muddy water
🏢 Built for — Basements, Pits, Compounds, Shop Floors
⚡ Supply — single and three phase
🔧 Genuine spares available

Sizing takes three numbers: how deep the water sits, how high it has to be lifted, and the discharge pipe size. Send us those and we will match the pump to the job rather than to the catalogue.

If you are buying in the middle of a flood you are already too late to compare. Sort it before the next spell.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#DewateringPump #Dewatering #SubmersiblePump #Monsoon #Waterlogging #BasementFlooding #FloodWater #DrainagePump #WaterPump #HousingSociety #BuildingServices #FacilityManagement #ConstructionSite #PlantMaintenance #Mumbai #Ahmedabad #IndustrialSupplies""",
},
{
 'slug':'booster-pumps', 'kind':'pump',
 'title':'Pressure Booster Pumps',
 'src':'uploads/pump/mini-force-i.webp',
 'img_alt':'Pressure booster pump',
 'badge':'Pressure boosting',
 'headline':'Top floor.<br><em>Same pressure.</em>',
 'subline':'Pressure boosting for high-rise flats, terrace tanks and mains '
           'that never quite reach the shower.',
 'plate_title':'Booster pumps', 'plate_ref':'Ex-stock',
 'rows':[('Output','0.5 HP to 1 HP',1),
         ('Head','Up to 42 m',1),
         ('Supply','Single phase',0),
         ('Inlet &times; outlet','25 &times; 25 mm',1)],
 'cta':'Tell us the floors and tank height',
 'hl':70, 'hl_story':84, 'shot':512, 'shot_story':760, 'key_w':185, 'val':31,
 'caption':"""Top floor. Same pressure.

Weak flow in the shower is almost never the tap. It is head — the vertical distance between your tank and the outlet — and on the upper floors of a Mumbai building there simply is not enough of it.

A booster pump adds the pressure the gravity feed cannot. Fitted correctly it is quiet, automatic, and you stop thinking about it.

⚙️ Output — 0.5 HP to 1 HP
📏 Head — up to 42 m
⚡ Supply — single phase
🔩 Inlet × outlet — 25 × 25 mm

One warning worth giving: a booster cannot invent water. If your tank runs dry or the inlet is undersized, a bigger pump makes noise, not pressure — and running dry is what kills the seal. Fix the supply first, then boost it.

Tell us the number of floors, the tank height and where the weak outlet is, and we will size it properly.

📞 98200 42210
✉️ besyndicate@gmail.com
🔗 bombayengg.net

#BoosterPump #PressurePump #WaterPump #HighRise #PlumbingSolutions #BuildingServices #HomeImprovement #WaterPressure #HousingSociety #FacilityManagement #Mumbai #Ahmedabad #Plumbing #IndustrialSupplies""",
},
]


def build_one(p, root):
    kind = p['kind']
    logo, brand_alt, brand_h, headbar = BRAND[kind]

    # Guard rail: the whole point of the brand rule is that it cannot drift.
    assert (kind == 'motor') == (logo == 'cg-motors.png'), \
        f"{p['slug']}: brand logo does not match product kind"

    d = os.path.join(HERE, p['slug'])
    os.makedirs(os.path.join(d, 'assets'), exist_ok=True)
    os.makedirs(os.path.join(d, 'out'), exist_ok=True)

    # product image → white-flattened JPEG at a size the canvas can use
    src = os.path.join(root, p['src'])
    subprocess.run(['convert', src, '-background', 'white', '-flatten',
                    '-resize', '1600x1600>', '-unsharp', '0x0.7+0.6+0.02',
                    '-quality', '95', os.path.join(d, 'assets', 'product.jpg')], check=True)

    rows = '\n'.join(
        f'      <div class="plate__row"><span class="plate__k">{k}</span>'
        f'<span class="plate__v{" si" if si else ""}">{v}</span></div>'
        for k, v, si in p['rows'])

    tpl = open(os.path.join(HERE, 'base.html'), encoding='utf-8').read()
    for token, val in [
        ('TITLE', p['title']), ('HEADBAR', headbar),
        ('BRAND_LOGO', logo), ('BRAND_ALT', brand_alt), ('BRAND_H', brand_h),
        ('SHOT_MOD', ' shot--photo' if p.get('photo') else ''),
        ('IMG_ALT', p['img_alt']),
        # a post can opt out of the chip — on the nameplate post the plate fills
        # the panel and the headline already names it, so the chip only collided
        ('BADGE_TAG', ('    <span class="badge">' + p['badge'] + '</span>') if p.get('badge') else ''),
        ('HEADLINE', p['headline']), ('SUBLINE', p['subline']),
        ('PLATE_TITLE', p['plate_title']), ('PLATE_REF', p['plate_ref']),
        ('PLATE_ROWS', rows), ('CTA', p['cta']),
        ('NOTE', ('    <p class="note">' + p['note'] + '</p>') if p.get('note') else ''),
        ('HL', p['hl']), ('HL_STORY', p['hl_story']),
        ('SHOT_H', p['shot']), ('SHOT_H_STORY', p['shot_story']),
        # WA trades 8px off the top pad for 44px more at the bottom, so the
        # photo gives back the 36px difference.
        ('SHOT_H_WA', p['shot_story'] - 36),
        ('KEY_W', p['key_w']), ('VAL', p['val']),
    ]:
        tpl = tpl.replace('{{%s}}' % token, str(val))
    assert '{{' not in tpl, f"{p['slug']}: unreplaced token"
    open(os.path.join(d, 'template.html'), 'w', encoding='utf-8').write(tpl)

    open(os.path.join(d, 'ig-caption.txt'), 'w', encoding='utf-8').write(p['caption'].strip() + '\n')

    # render both artboards
    for hid, size, out in [('post',  '1080,1350', f"{p['slug']}-post.png"),
                           ('story', '1080,1920', f"{p['slug']}-story.png"),
                           ('wa',    '1080,1920', f"{p['slug']}-whatsapp.png")]:
        subprocess.run([CHROME, '--headless', '--disable-gpu', '--no-sandbox',
                        '--hide-scrollbars', '--force-device-scale-factor=1',
                        '--virtual-time-budget=15000', f'--window-size={size}',
                        f'--screenshot={os.path.join(d,"out",out)}',
                        f'file://{os.path.join(d,"template.html")}#{hid}'],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        # PDF alongside the raster: printing keeps type as real text, so the file
        # lands in Canva as editable text boxes rather than a flat picture.
        subprocess.run([CHROME, '--headless', '--disable-gpu', '--no-sandbox',
                        '--virtual-time-budget=15000', '--no-pdf-header-footer',
                        f'--print-to-pdf={os.path.join(d,"out",out.replace(".png",".pdf"))}',
                        f'file://{os.path.join(d,"template.html")}#{hid}'],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        jpg = os.path.join(d, 'out', out.replace('.png', '.jpg'))
        subprocess.run(['convert', os.path.join(d, 'out', out), '-quality', '92',
                        '-strip', jpg], check=True)
        subprocess.run(['convert', jpg, '-resize', '520x', '-quality', '82',
                        os.path.join(d, 'out', f'thumb-{hid}.jpg')], check=True)
    print(f"  {p['slug']:<20} {kind:<5} {logo}")


if __name__ == '__main__':
    root = '/home/bombayengg/public_html'
    only = sys.argv[1] if len(sys.argv) > 1 else None
    for p in POSTS:
        if only and p['slug'] != only:
            continue
        build_one(p, root)
    print("done — nothing published")
