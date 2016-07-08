<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

$parts_array = '
SMNS 23100-01 ISS-03
SMNS 23101-02 ISS-02
SMNS 23109-03-ISS-02
CSRX 905-5185-003
212AR-L1A
33105L3REVG
TLTD AUA293SB I3
NECA Y4102A1-V05JD
PLSE HDSL-64
NECA Y4100BV02DH
AUB60 S1
AUB63 S1
AUB68 S2
MC97761A1 I1
SFSC 4012765
DATL E80-00611-00 REV-D
STBG 817578-026-006
829A L1A S1
WSCM 730545I01
WSCM 91-3440-39
509509E 
509478
ECIT 40381Q-I3 L3
19PRGTP-LT
CPCI350QDC38
NMSF14M0 4X1MBB F CONN 0DB 
TSLS 80.5623
FB0162481A
RPM1000C
NNTM NT7B75AAAG  S01
NMCF18M0
LKAM 91826-02 ISS01
817578026
NT8X06BA
651501
817540126A
1500004627
814529026
CU ISSUE2 2W PLAR1/2 FX/RD
38034284
38103301
9001622101
9002310101
A4800A93
9113A
9112301
5592001
50HFRG1
50HFRG1LT
9186901M2
51051300
9ZXACDXCT3DM
817603576
38034204
38034300
9144802
FB016223A
9144801
91VIP364A
50HFRGTLT
38107701
66312B
904002215
L8706000
4401
814711566
202T
NT8X06AB
NT2T13AB
904191503
S30810Q1091X201
9186910
900-25239-01
LKAM 900-23109-03
817518046
817503526
814505526
814256026
814255036
814254026
904191402
9002310103
651601
FB0162221AISSUE13
38453800
38132900
AGCY FB026798-A-I01
EZT3ESCTRLB20
9002310102
9002416601
SMNS 9002310001
4100323
FB016330A-ISS1C
66312D
234128870070
234128950010
NT6C14PA
9PR8906RG1
8102
GTEA FB0163041A
817552526
814432026
38441540
9182402
COOK NT6M66AL S004
COOK NT6M63FE R001
NNTM NT7F25AB S01
11111
SNTR 710-0001-101 REV A
11076A REV 01
11073A
11110-REV-02
59087B-64
NECA S9326B-FANU/001-G1
TSLS 81.4251
ALCL 3FE51185BA01
MC3F003A1 I1
MC3F030A1 I1
MC3F020A1C I1
MC3F024A1C I1
MC3F001A1 I3
UN337B S1
TN914 7
CINA 170-3960-902 C
CSCO 800-03316-04
800-02252-04
TN1394B S1
TN2167 S2
CSCO 68-0775-08
CSCO 68-2051-05
11091F S5
CSCO 800-26320-01
CGDT 9145E-VH15
CGDT 9145E-VH19
CGDT 9145E-VE17
CGDT 9145E-VH06
CGDT 9145E-VE13
CGDT 9145E-VE03
CGDT 9145E-VE19
JPNW SCB-MX960-S-A
JPNW SCB-MX960-S-E
JPNW RE-S-1800X4-8G-S-C
JPNW DPCE-X-40GE SFP
JPNW DPCE-X-40GE-SFP-R
TMSL 090-40050-01 IA REV C
TMSL 090-58021-01
TMSL 090-72010-01 REV-C/F
089-72050-01 REV D
ASTN 23478303-002-0-REV-C
ASTN 23478301-000-0
TMSL 090-55545-01-REV-F
TMSL 090-55591-01 REV F
TMSL 090-72050-97-REV-B
TMSL 090-72050-71-REV-A
ASTN 23478272-000-0
ASTN 23478272-004-0-REV-A
TMSL 089-55581-01-REV-F
TMSL 0900-55581-01-REV-K/L
TMSL 090-55514-02-REV-G
TMSL 090-55514-02 REV AF/AK
TMSL 090-55542-01-REV AA/AC
05551202REVF
ITTN 628170-000-005
ALCL 628270-000-005
ALCL 628196-000-001
TLTD FX04520I5
ALCL 628061-000-001
4
ADTN 1291021 L3 REVJ
PLSE PDU-ACRG-2-REV-A
ALCL 628035-000-001
ALCL 628037-000-001
LKAM 9110301
LKAM 9140801
AGCY FB-01628-0A
AGCY FB-01657-7A
LKAM 9144803
LKAM 9183011
LKAM 9140901
LKAM 9142001
LKAM 9142501
LKAM 9143901
LKAM 9143801
LKAM 918201 IF3M1
LKAM 91821-02 I5 M1
LKAM 9146701
XELC L8400-100
SCSP 91821-03 ISS 04
SCSP 25004-12
LKAM 91470-01
LKAM 900-25004-13
LKAM 91452-11
LKAM 91466-07
LKAM 91425-02
LKAM 91418-01
LKAM 91407-12
AEK7 S1
MC97027A1 I2.D
AEK11 S2
AEL27 S1
MC97022A1 I2
KNTX 15952013
LA2 S1
ADTN 121201761 REV-D
TELC CCA162G4 I1.1
LC CCA120G3-I1-REV-BR
TELC CCA120G3E-I1
2,C,D,E
ADCP N-MAF30FA
ADCP N-MV48DC-B1
TLTD SDS5486CI3
MPU2 S1
UN732 S1
DAC120B S1
MMA3 S1
SN216 2
SN217 S1
TN1345 5
2
MC5D068A1 I1
MC5D073A1 I6
MC5D068A1 I4
M5D085A1 I4
MC5D195A1 I1
MC5D217A1 I8
MC5D198A1 I1
TN329B S22
MC5D195A1 I1B
UN197 S1
TN244 S5
TN340B 4A CC
TN315 S10
TN338B S1
TN862 S1
TN1617 S1
TN340 S15
UN503 S1
UN504 S1
TN1284 S1
982TTB S1
KBN1 S10
KBN2 10*
SN730 S1
KBN8B S1:6
BKD 11 S1:1
UN589 S3:4
MMC101 S1
MMD100 S6
982CAG S1
MMD101 S11
MMD100 S10
TN1873 S10
MMD100 S13
TN1873 S11
982TSC S3
MMD100 S12
UN361 S11
KBN7 S2
9824AD S1
SN516B S1
982AAB S1
KBN2 S12
UN359 S11:16
KSU2 S1
TN1424 S2
101
UN396 S2:3
9824BD
UN376C S 1:2
DAC100 S3
BNP2 S1
UN560 S6:8
KBN22C S1
0
FCD100 S1
UN589 S3
3
6820PL-07W-B49-D53
SCSP S30810-Q1328-X1-4-3
STBG 817561-526-009
STBG 817540-026-004
SCSP S30810-Q1112-X1
SCSP S30810-Q1113-X1-8-20
SCSP S30810-Q1073-X102-6-1
SCSP S30050-B5681-X100-3
SCSP S30807-U2594-X400-3
SCSP S30810-Q1072-X1-8-5
SCSP S30810-Q1190-X1-7-5
GTEA FB16226-A-I03A
GTEA FB16309-A-I02
GTEA FB16093-A-I01
GTEA FB16186-A-I02
SCSP S30810-Q1155-X I2
STBG 817552-526-000
AGCY FB-16252-A014
AGCY FB-16192-1A005
AGCY FB-16271-A001
AGCY FB-16337-A
GTEA FB16332-AI02
SCSP S30050-Q6101-X-6
AGCY FB-26798-A
SCSP S30810-Q1127-X1-3-6
SCSP S30810-Q1345-X1-3-4
NNTM NT3T45DB SA
NNTM NTRX50NB REV03
NNTM NT9X13FA S21
NNTM NT0X36AA S01
NNTM NT6X92BC S01
NNTM NT0X87AA
ERSN 8501-93
TELC CCA219G1-I2
TELC CCA219G1-I3
NPPN R2937A
NECA R9790A
ALCL 622-7590-001-REV-BV
CLNS FD-38B-1
CLNS 6227541-004 REV-F
NECA X1836A
NECA R9778A
GTEA FB26826A I08
NECA X4610A
NECA X4611A
NECA X9238B-V01AE
CLNS FD-32K-1-REV AF
CLNS 622-8291-001-REV-AW
CLNS FD-33L-1-REV AL
TSLS 81.65302A-REV-B
ALCL 3HE00030AA01
WSNW PA-00029-01
TSLS 81.65304-REV-D
TSLS 81.65206-REV-E
TSLS 81.65204-REV-D
TSLS 81.65205-REV-D
TSLS 81.65205-REV-C
TSLS 81.65201-REV-D
TSLS 81.65202-REV-F
TSLS 81.65202-REV-D
162-0023-900 REV D
JPNW SFM-16-S B
TSLS 81.65360-REV-G
TSLS 81.65350-REV-D
TSLS 81.65101-REV-B
TSLS 81.65102-REV-A
TSLS 81.65103-REV-A
ALLU 3HE05181AA01
162-0003-900
TSLS 81.65351
TSLS 82.65306B-1310-SC-REV-A
TSLS 81.65306B-1310-SC-REV-D
TSLS 81.65307E-1310-SC-REV-A
TSLS 81.65307E-1310-SC-REV-B
TSLS 81.65307A-1310-SC-REV-B
CSCO 341-0074-01
TSLS 81.65203-REV-B
TSLS 81.65203-REV-C
TSLS 81.65053-REV-B
TSLS 81.65134-REV-B
ALCL 3HE00232AA01
ALCL 3HE00235AA01
JPNW XFP-10G-L-OC192-SR1
ALCL 3HE03623AA01
ALCL 3HE04934AA01
ALCL 3HE04937AA01
ALCL 3HE05036AA01
ALLU 3HE05950AA01
ALLU 3HE05948AA02
TSLS 81.65101-REV-C
TSLS 81.65103-REV-C
3HE01172AA02
CSCO 68-3106-01
ALCL 3HE05107AA01
CSCO 68-2614-02
ALCL 3HE00020AB01
ALCL 3HE00229AB01
ALCL 3HE00566CA01
ALCL 3HE00867CA01
ALCL 3HE00028CA01
ALCL 3HE00029CA01
CSCO 800-16968-10
ALCL 3HE03666AA01
3A 5WIRE FAN
TSLS 81.9294
TSLS 82.6003M1
TELC CCA081G3-I1
NNTM NT0H41AB S01
CINA 130-0321-910
00229000002REVB
TLGD TLGD-DMU/EOC
TLGD TLGD-DMU/EEC
TLGD TLGD-DMUPLUS-L
TLGD TLGD-DMUPLUS-M-I3
TLGD TLGD-DMUPLUS-L-I3
TLGD TLGD-DMUPLUS-M-I4
TLGD TLGD-DMUPLUS-L-I4
TNCL 380-300-00
MC2P002B4 1.A S2
TLGD TLGD-DAF-I2
TELC CCA005G1
CCA149G1-I2 REV CR6
NECA X3351A
NECA X6716A-V01BB
NECA X6717A-V01AC
NPPN R7603A
ADTN 1181041L2
NECA X0417A
NECA X4335C
NECA X7135A
NECA X4335C V01B
NECA X0417C-V01A
NECA Y0565A V020
ECA X1832B1
NPPN R2932 A02
NECA X0415 C1
NECA X0416A
NECA X0416E
ADTN 1181004L2
NPPN R3858 A01
NECA X0421A
NPPN Q7956A
NPPN R3855A
NPPN R2933A
NECA X1037C
TELC CCA121G2-I1
TELC CCA121G2E-I1
TELC CCA513G1
85003L1REVH
SMHK 900-24326-01
NECA X0420A
NPPN R3848C
NPPN Q7373A3
NPPN R2905  A01
NDR 824RTP 86Z
NECA R9785 A01
3
1
3
2
WSCM 612256I03
TSCM MFT-1215L1I01
TSCM MFT-1206L2
TSCM MFT-1205L1
2
1
NNTM NTN372AA S02
TLTD DDI5740
5
JPNW EX3-ACPSBLNK-PNL
PNID 751321C
NT4X00A6
AGCS FB016280 A001
VLPW BC500-A02-10VC
ENES 545476
LNPW MCR1B-MCR2B
VLPW BC2000-A40-10VC
VLPW BC2000-A93-10VC
TYCO QS841A AT1 S1
LORN 58211570001
TYCO QS865A R5 S1
ADPL AP5C56AA S01
TYCO QS865A
VLPW C0200A-VC
ENES 1R483200
LNPW CC109161758
LORN 486894500
LORN 486530300
LORN 486527800
LORN 486803600
VLPW BC2000-A02-10VC S2
LORN 427215900
437423100 METER PNL
418AA S2
1,MOD B
1
1
208D S1
208A S1:2
DGSW 261-1000-057 REVL
DGSW 261-1000-058-REV-A
DGSW 261-1000-086-REV-A
PMP0483A
427AB S1
208F1 S1
495NA S3
429AA-VER2 S1
BGB1 S1
LORN 487109500
DATL B11-46020-41 REV A
RTEC 77-811-47
TSLS 81.9461
YL1B CP
WP61 CP
2,A
WN2D CP SER3
WN4 S2
WN4 CP
WP51 S1
WP55 S3
R2121-01A-REV-F
RYNT R2121-01A
SM88C S1
SM90 S1
SM86B S1
RYNT R2106-03A
SPQ400C S1
BDK270 S1
NNTM NT4K53AB R05
BDJ200 S8B
ADFB 0120-0139-1A
ADFB 0110-0119
ADFB 0101-0030-1C
ADFB 0120-0046-1A
ADFB 0101-0001-4C
ADFB 0101-0008
ADFB 0110-0239-1A
ADFB 0100-0132-1C
ADFB 0110-0246-1A
ADFB 0110-0141-1E
ADFB 0110-0042
ADFB 0110-0095-5D
ADFB 0110-0160-1A
ADFB 0110-0139-1B
ADFB 0101-0023-1D
ADFB 0101-0019
ADFB 0101-0007-3A
ADFB 0700-0004
ADFB 0700-0014-1A
ADFB 0700-0001-4F
ADFB 0120-0128-1A
ADFB 0120-0150-1A
ADFB 0120-0147-1A
ADFB 0120-0153-1A
ADFB 0120-0151-1A
ADFB 0120-0152-1A
ADFB 0120-0015
ADFB 0120-0015-4A
ADFB 0120-0012-1G
ADFB 0101-0004
ADFB 0101-0018 REV 2
FJTU FC9681EL31
FJTU FC9681ED33
40382Q-I3-L2
IDNK 103UDP11B2
ADTN 1181308L8-REV D
ADTN 1181007L2
ADTN 1181500L1
GDD405REVN
ADTN 1221002L3 
ALCL 3EM16684AB01
DGSW 509-0000-163-REV-B
6B
ALCL 300-8276-900-REV-B
5
2
TNCL 384-110-00
0
FJTU FC9511TSS2-I04
FJTU FC9607HT25
NNTM NTN440KA S01
TELC CCA595G1
FJTU FC9511TAS1-I02
FJTU FC9511HUB3-I04
FJTU FC9616H6E2-I05
FJTU FC9511DMS1-I05
NNTM NTN455AA
FJTU FC9511MXS2-I05
FJTU FC9511DMS2-I03
ALCL 3AL07783DE01
ALCL 3AL07783DD01
ALCL 625612-000-001
ALCL 3AL54507AB01
T939A S1
ALCL 3AL00324AA01
CLNS 644-0069-001-B
ALCL 3AL00238AA02
ALCL 695-2372-308
41A1C S1
41A2C S1
41A3C S1
41A4C S1
41A5C S1
41A6C S1
41A8C S1
41A9C S1
41A10C S1
41A11C S1
41A12C S1
41A14C S1
41A15C S1
41A16C S1
ALCL 3AL45738AA01
41BB S1
TELC CCA475G1-REV-A
FJTU FC9612SVL4
LAA27 S3
NECA Y0455DB-V01AB
CLNS 644-0074-001-ISS-01
LEA104 S1
LEA105 S1
LEA102 S1
FJTU FC9607HRL1-I10
NECA Y0455DB-V01AD
FJTU FC9607SVT4-I08
ALCL 3AL45416AA REV06
ALCL 3AL00226AA01
ALCL 3AL00124AB REV 06
NECA Y3279AA-V03BH
NECA Y0485AA-V01AF
FJTU FC9607MP2H-T12
FJTU FC9607SVT4-I07
FJTU FC9607HTL1-I08
ALCL 3AL07374DC01
FJTU FC9607HRL1-I14
FJTU FC9511RCS3-I05
739B1 S1
NECA Y1635A-V01D
NECA Y1635A-V01A
NECA Y1636A1-V01A
NECA Y1636FA-V01A
NECA Y1636FA
NECA Y1636FF-V01E
CSCO 800-24897-02
CSCO 800-24903-02
NECA Y1635A1-V01A
NECA Y1635FA-V01A
NECA Y1635FA-V01C
NNTM NTN312AA S01
NNTM NTN355AA S01
ADTN 1184544G2
FJTU FC9580FTX5
FJTU  FC9580M3C7-I03
FJTU FC9580GSL5
TF4 S1
UN375 S1
KBN15 S3
TN1820C S2
UN583 S10
UN583 S10.14
UN375G S1
MC3T101A1 I3
1A
UN375B S2
UN580 S3L
PACKCKT UN933 S7:9
UN375D S1
UN597 S4
2B-S2
DGSW 300-0757-906-REV-A
TLMR 500-9000-001T-REV-A
DGSW 300-1468-904-REV-A
DGSW 300-0708-905-REV-A
ALCL 300-1628-901-REV-F
TLMR 300-1508-906T-REV-A
1181018L3A
ADTN 1181018L1ST
DGSW 300-0334-900-REV-AA
TG79 S6
DGSW 300-0303-900-REV-AA
825310  REV F1
DGSW 300-0334-900
DGSW 300-0438-900
DGSW 300-0438-901-REV-A
DGSW 063-3600-01
CARD PGTS 150-1266-01-X00 R05
ADTN 1223424L7-REV D
23026L7REVN
ADTN 1181413L4 REV G
ADTN 1223401L4-REV D
81113L4REVR
ADTN 1223003L4-AD REV J
ADTN 1223026L7 AD REV H
ADTN 1181113L4 ADREV E
ADTN 1223407L9-ADREV K1
ADTN 1181113L4- AD REV M4
ALCL 300-1587-900-REV-F
ALCL 300-1585-900-REV-G
PGTS 150-1111-85
PGTS 150-1111-75
ADTN 1221426L7
ADTN 1221426L6
ADTN 1223001  L4 REV D
1223003L4 REV B
23026L7REVF
ADTN 1221001 L4 REVH
1222001L1-REV-C
ADTN 1221401L4
ADTN 1222404L1 REV F
1222401L4 REVB
ADTN 1223401L4
ADTN 1222426L7
ADTN 1223426L7
ADTN 1223003L4 ADREV J1
ADTN 1223026L7 ADREV J
ADTN 1181413L4 REV F
1181413L4-REV-C
ADTN 1223403L4 REV-D
ADTN 1223426L2-ADREV-D1
ADTN 1181113L4-ADREV L
PGTS 150-1111-06
AWP15 S1
DGSW 300-0713-906-REV-A
TSLS 81.5381-REV-H
DGSW 300-0311-900
DGSW 300-0438-901-REV-L
DGSW 300-0434-900-REV J
ALCL 300-0434-904-REV-H
DGSW 300-0433-901-REV-J
DGSW 300-0404-904-REV-C
DGSW 500-0000-078-REV-D
ALCL 300-0434-905-REV-B
DGSW 300-0433-905-REV-A
TSLS 81.5301C-REV-C
939C S1
WSCM 342311I03-LB
AEK86B S1
TLTD IOR7231LSI2
PGTS 150-1268-12
NGTS 150-1268-01 REV 4
HKMN 3204-3101+02 REV B6
HKMN 3204-01-REV-A2
TSLS 81.5596-REV-G
CLNS 40C16228871001-REV-AA
DGSW 300-1508-901-REV-B
ADCP D3M-BM2001
DGSW 300-1799-900-REV-B
DGSW 300-1799-901-REV-B
AWR6 S3
AWP8 S2
AWR12 S1
AWP6 S2
AWS3 S1
AWS5 S1
CLNS 622-967002-ISS01-REV-G
ERA4 S2
TSLS 81.55060-REV-A
CLNS 622-9675-001
TSLS 81.9001
2A
HTPD 4935A
ETCS ETC-1052-SAB
ADCP 1201580
ALCL 3EC17385BA04
ALCL 3FE00168AA02
ALCL 3FE65737BA01
ALCL 3FE00168AA06
ADTN 1179742L2
3FE24322AA06
JDSO SC-HOME--V3
ALCL 3HJ20005BA01
3FE243232AA03
ALCL 3FE24324AB02
ALCL 3FE24323AC01
ALCL 3FE26509AD01
ALCL 3FE24324AB06
ALCL 3EC37779AC01
ALCL 3FE25773AA01
ALCL 3FE24318AF01
3FE23526AA05
3FE25389AA02
ALCL 3FE25676BA01
ALCL 3FE23086AA13
ALCL 3FE20806AC02
ALCL 3FE23086AA14
ALCL 3FE61265AA01
TSLS 81.4003C-REV-AA
WBST TLC412
CNTS 44321320
CSCO 800-22845-01
CSCO 800-24912-01
800-21448-03 A0
TSLS 81.71126A-REV-A
TSLS 81.71532-20-REV-A
TSLS 81.71532-30-REV-A
TSLS 81.71532-50-REV-A
FJTU FC9682L2C1-I03
FJTU FC9682M2C1-I02
FJTU FC9682RMC1-I02
FJTU FC96828TC1-I02
FJTU FC9682GUC1-I07
CROP 82.71328U-REV-F
TSLS 82.71328U-REV-C
FJTU FC96828TC1
FC9682U1C1-I04
CSCO 800-26772-02
FJTU FC9682CMC1-I02
CSCO 800-27357-01
TSLS 81.7100DCM-BLK-REV-A
FJTU FC9682FAN6
CSCO 800-23907-02
TSLS 81.7100HDP-P-REV-A
TSLS 81.7100PP-R-1M-REV-B
CSCO 800-25216-05
MEM-CFI1024 S2
FJTU FC9565W8C1-IAA
CROP 82.71714-R5-REV-F
JDSO 21095552-002
CROP 81.71710-REV-B
TSLS 81.71780-REV-A
TSLS 82.71714-R5
FJTU FC9682M2U1
FJTU FC9565ALC1
FJTU FC9565ASC1
TSLS 81.71188-IR-R5
TSLS 81.71188-LR-R5
TSLS 81.71188-ER-R5-REV-A
TSLS 82.71532-20-R5
TSLS 82.71532-30-R5
TSLS 81.717271-REV-A
TSLS 81.71887B-R5
TSLS 81.714144-R5
TSLS 81.714188-R5
CROP 81.71730A-REV-D
TSLS 81.S1GBESX1851M-REV-A
FJTU FC9565S9B1
FJTU FC9565EXX1-I03
TSLS 81.71110A-R5
CROP 81.71T-SPMRSR1-R6 -REV-B
FJTU FC9565TPE1-I06
FJTU FC9565TGD1-I10
FJTU FC9682QMC1-I02
FJTU FC9565TGD1
CROP 81.71M-HGTM-R5-REV-F
FJTU FC9565TBA1-I11
CROP 81.71L-HDTG-R5-REV-G
CROP 81.71M-HGTMM-R5 -REV-E
WRRN 50HFRG-LT
9822DY';
	include 'inc/dbconnect.php';
	include 'inc/format_part.php';
	include 'inc/keywords.php';
	$lines = explode(chr(10),$parts_array);
	foreach ($lines as $k => $line) { 
		$line = trim(preg_replace('/^([A-Z]{4}[[:space:]])(.*)/i','$2',$line));
		if (strlen($line)<=2) { echo '<BR>'; continue; }
//		$line = str_replace(' ','-',$line);
		$h = hecidb($line);
		$part = format_part($line);
		foreach($h as $r) {
			$e = explode(' ',$r['part']);
			$part = preg_replace('/[^[:alnum:]]+/','',format_part($e[0]));
			break;
		}
		echo $part.'<BR>';
	}
?>