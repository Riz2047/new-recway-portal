function pdf_gene(obj) {
    $.ajax({
        type: "POST",
        url: "../includes/pages.php",
        data: {
            'get_inte_data': obj,
        },
        dataType: "json",
        success: function (response) {
            if (response != '') {
                var order_id = response.order_id;
                var name = response.name.replace(/\s+/g, '').substring(0, 1) + response.surname.replace(/\s+/g, '').substring(0, 1);
                var vasc_id = response.vasc_id;
                var refperson = response.referensperson.replace(/\s+/g, ' ');
                var cus_name = response.cus_name;
                var cus_company = response.cus_company;
                var place = response.place_name;
                var staff = response.staff;
                var interview_date = response.booked;
                var cri_check = response.criminal_record;
                var soc_check = response.social;
                var eco_check = response.economy;
                var bk_date = response.background_check_date;
                var now = new Date();
                var hours = now.getHours();
                var minutes = now.getMinutes();
                var seconds = now.getSeconds();
                if (place && interview_date) {
                    place_inter_date = place + '          ' + interview_date
                } else if (place) {
                    place_inter_date = place
                } else if (interview_date) {
                    place_inter_date = interview_date
                }
                hours = (hours < 10 ? '0' : '') + hours;
                minutes = (minutes < 10 ? '0' : '') + minutes;
                seconds = (seconds < 10 ? '0' : '') + seconds;

                var currentTime = hours + ':' + minutes + ':' + seconds;

                var day = now.getDate();
                var month = now.getMonth() + 1;
                var year = now.getFullYear();

                day = (day < 10 ? '0' : '') + day;
                month = (month < 10 ? '0' : '') + month;

                var currentDate = day + '-' + month + '-' + year;


                const checkboxString = "☐";
                const checkedCheckboxString = "☒";
                const def_check = "Ja ☐	Nej ☐";

                if (eco_check == 1) {
                    eco_check = "Ja ☒	Nej ☐";
                } else if (eco_check == 0) {
                    eco_check = "Ja ☐	Nej ☒";
                } else {
                    eco_check = "Ja ☐	Nej ☐";
                }

                if (soc_check == 1) {
                    soc_check = "Ja ☒	Nej ☐";
                } else if (soc_check == 0) {
                    soc_check = "Ja ☐	Nej ☒";
                } else {
                    soc_check = "Ja ☐	Nej ☐";
                }

                if (cri_check == 1) {
                    cri_check = "Ja ☒	Nej ☐";
                } else if (cri_check == 0) {
                    cri_check = "Ja ☐	Nej ☒";
                } else {
                    cri_check = "Ja ☐	Nej ☐";
                }

                function loadFile(url, callback) {
                    PizZipUtils.getBinaryContent(url, callback);
                }
                loadFile(
                    "./../assets/docx/default_interview_template.docx",
                    function (error, content) {
                        if (error) {
                            throw error;
                        }
                        const zip = new PizZip(content);
                        const doc = new window.docxtemplater(zip, {
                            paragraphLoop: true,
                            linebreaks: true,
                        });
                        doc.render({
                            place_inter_date: place_inter_date,
                            // inter_date: interview_date ? interview_date : '',
                            staff: staff ? staff : 'N/A',
                            time: currentTime ? currentTime : '',
                            vasc_id: vasc_id ? vasc_id : 'N/A',
                            name_ini: name ? name : '',
                            ord_id: order_id ? order_id : '',
                            inv_ref: refperson ? refperson : '',
                            company: cus_company ? cus_company : '',
                            bk_date: bk_date ? bk_date : '',
                            eco_check: eco_check,
                            soc_check: soc_check,
                            cri_check: cri_check,
                            current_date: currentDate,
                        });

                        const blob = doc.getZip().generate({
                            type: "blob",
                            mimeType:
                                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                            compression: "DEFLATE",
                        });
                        saveAs(blob, order_id + "_" + name + "_"+interview_date+".docx");
                    }
                );



                // $.ajax({
                //     url: 'convert_doc.php',
                //     type: 'GET',
                //     success: function (data) {
                //         // Process the file content (e.g., fill placeholders)
                //         var updatedContent = data.replace('{place}', 'John Doe');
                //         console.log(data);
                //         // Create a Blob object from the updated content
                //         var blob = new Blob([updatedContent], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

                //         // Create a link element to trigger the download
                //         var link = document.createElement('a');
                //         link.href = window.URL.createObjectURL(blob);
                //         link.download = 'updated_document.docx';

                //         // Append the link to the document body and trigger the download
                //         document.body.appendChild(link);
                //         link.click();
                //         document.body.removeChild(link);
                //     },
                //     error: function (xhr, status, error) {
                //         // Handle errors
                //         console.error('Error:', error);
                //     }
                // });

                //         var now = new Date();
                //         var hours = now.getHours();
                //         var minutes = now.getMinutes();
                //         var seconds = now.getSeconds();

                //         hours = (hours < 10 ? '0' : '') + hours;
                //         minutes = (minutes < 10 ? '0' : '') + minutes;
                //         seconds = (seconds < 10 ? '0' : '') + seconds;

                //         var currentTime = hours + ':' + minutes + ':' + seconds;

                //         window.jsPDF = window.jspdf.jsPDF;
                //         const doc = new jsPDF({
                //             orientation: 'portrait',
                //             unit: 'mm',
                //             format: 'a4'
                //         });
                //         var lineHeight = 5;
                //         var maxWidth = 150;
                //         const pageCount = 17;
                //         const logoUrl = 'assets/images/pdf_com.webp';
                //         const logoImage = 'assets/images/30_op.png';
                //         var currentDate = new Date();
                //         var year = currentDate.getFullYear();
                //         var month = currentDate.getMonth() + 1;
                //         var day = currentDate.getDate();
                //         month = (month < 10 ? '0' : '') + month;
                //         day = (day < 10 ? '0' : '') + day;
                //         var formattedDate = year + '-' + month + '-' + day;
                //         for (var i = 1; i < pageCount; i++) {
                //             doc.setFont('helvetica', 'normal');
                //             doc.setFontSize(7.5);
                //             doc.setTextColor(128, 128, 128);
                //             doc.text('Recway blankett för', 38, 16);
                //             doc.text('säkerhetsprövningsintervju, reviderad', 17, 19);
                //             doc.text(formattedDate, 48, 23);
                //             doc.setTextColor(36, 36, 36);
                //             doc.addImage(logoUrl, 'PNG', 13, 25, 48, 16);
                //             const centerX = (doc.internal.pageSize.getWidth() - 160) / 2;
                //             const centerY = (doc.internal.pageSize.getHeight() - 50) / 2;
                //             doc.addImage(logoImage, 'PNG', centerX, centerY, 160, 50);
                //             const textWidth = doc.getStringUnitWidth(`Sida ${i}`) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                //             const rightX = doc.internal.pageSize.getWidth() - textWidth - 24;
                //             doc.text(`Sida`, rightX, 38);
                //             doc.setFontSize(11);
                //             doc.setFont('helvetica', 'bold');
                //             doc.text(`${i}(17)`, rightX, 43);
                //             doc.setFontSize(10);
                //             doc.setFont('helvetica', 'normal');
                //             doc.setTextColor(0, 0, 0);
                //             if (i == 1) {
                //                 doc.setFont('times', 'bold');
                //                 doc.setFontSize(11);
                //                 doc.text('Förberedelser inför säkerhetsprövningsintervjun', 25, 60);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.setFontSize(10);
                //                 var subParagraphText = "Innan en säkerhetsprövningsintervju genomförs är det avgörande att den ansvariga för intervjun är noggrant förberedd. Det är viktigt att ha kunskap om de frågor som ska ställas och i vilken ordning de bör diskuteras. Det är också fördelaktigt att identifiera specifika frågor som är relevanta för den aktuella tjänsten eller uppdraget och som kan behöva tas hänsyn till baserat på befintlig information om den som ska prövas.";
                //                 var splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 65 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Varför genomförs säkerhetsprövningsintervju och vad är det?', 25, 93);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 var subParagraphText = "Syftet med säkerhetsprövningen är att bedöma om individen är lämplig för sådan verksamhet ur ett säkerhetsperspektiv.Det innebär att prövningen inte utvärderar kompetens, färdigheter eller prestationer, utan fokuserar i stället på att fastställa om personen är lojal mot de nationella intressen som skyddas enligt säkerhetsskyddslagstiftningen och om personen är pålitlig från säkerhetssynpunkt.Säkerhetsprövningsintervjun och dess syfte gäller även för organisationer som inte omfattas av säkerhetsskyddslagen.Denna praxis är relevant för alla organisationer, oavsett om de är offentliga eller privata, och oavsett om de har direkt koppling till rikets säkerhet eller inte.Säkerhetsprövning är särskilt viktig när det handlar om att hantera känslig information teknologi, företagshemligheter eller andra resurser som kan vara av strategisk betydelse för organisationen.Genom att utföra säkerhetsprövningar kan organisationer säkerställa att de anställer eller samarbetar med personer som inte utgör en potentiell säkerhetsrisk.Detta är avgörande oavsett om organisationen omfattas av säkerhetsskyddslagar eller inte, eftersom säkerhetsincidenter kan ha allvarliga konsekvenser för verksamheten och dess intressen.";
                //                 var splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 98 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFont('times', 'bold');
                //                 doc.setFontSize(11);
                //                 doc.text('Förklara syftet med intervjun:', 25, 167);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 subParagraphText = "Innan intervjun inleds ska vi tydligt förklara för kandidaten varför de har blivit inbokade och vad ett säkerhetsperspektiv.Det innebär att prövningen inte utvärderar kompetens, färdigheter eller";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 172 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Tydlighet kring beslut under intervjun:', 25, 185);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 subParagraphText = "Under intervjun är det viktigt att påminna kandidaten om att vi inte kommer att ge några beslut om deras status eller resultat omedelbart. Vår process innefattar att sammanfatta intervjun och skicka den till kunden för vidare utvärdering.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 190 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Respekt för kandidatens integritet:', 25, 209);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 subParagraphText = "Vi ska vara medvetna om att många av de frågor vi ställer kan upplevas som privata. Därför är det viktigt att vara respektfulla och förstående gentemot kandidatens komfortnivå under intervjun.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 214 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Uppmuntra frågor från kandidaten:', 25, 232);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.setFontSize(10);
                //                 subParagraphText = "Informera kandidaten om att de gärna får ställa frågor om våra frågor eller processen när som helst under intervjun. Detta främjar öppenhet och förståelse.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 237 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFont('times', 'bold');
                //                 doc.setFontSize(11);
                //                 doc.text('Dokumentation och hantering av information', 25, 252);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 subParagraphText = "Informationen som kommer fram under intervjun beläggs med sekretess. Det betyder att endast ett fåtal kommer att kunna ta del av den – om det bedöms som viktigt i processen.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 257 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //             }
                //             if (i == 2) {
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Avslutning:', 25, 60);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text('- Repetera att vi kommer att summera intervjun och skicka resultatet till kunden.', 25, 65);
                //                 doc.text("- Påminn kandidaten om att kunden kommer att återkomma till dem för vidare information.", 25, 70);
                //                 doc.text("- Fråga om kandidaten har några frågor eller funderingar.", 25, 75);
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Information om säkerhetsrelaterad hotbild:', 25, 83);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 subParagraphText = "Det säkerhetspolitiska läget i Europa har genomgått betydande förändringar, och Sverige har nu en ökad militärstrategisk betydelse i regionen.Hotbilden mot vårt land har förändrats avsevärt under de senaste åren.Denna förändring har medfört att totalförsvarsplaneringen har blivit mer angelägen och att det civila försvaret har aktiverats för att möta de nya utmaningarna.Säkerhet är av grundläggande betydelse i alla samhällssektorer, och det är av särskilt värde att skydda information och verksamhet som anses vara samhällsviktiga.Många områden omfattas av säkerhetsskyddslagen, som innehåller bestämmelser som är avsedda att skydda mot hot som spionage, sabotage och terrorism.Denna lagstiftning är av yttersta vikt för att säkerställa att vi har nödvändiga skyddsåtgärder på plats för att hantera säkerhetsrelaterade hot som kan uppstå i en föränderlig värld.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 88 + (lineHeight * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setTextColor(36, 36, 36);
                //                 doc.setFontSize(11);
                //                 doc.setFont('times', 'bold');
                //                 doc.text('Intervju utan klarhet om syftet:', 25, 145);
                //                 doc.setTextColor(0, 0, 0);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text('Om det är en kandidat som inte vet varför hen har blivit inbokad, bör ni be kandidaten om ni ska', 25, 150);
                //                 doc.text("fortsätta med intervjun eller inte. Oavsett resultatet av intervjun, omedelbart efter intervjun, ska", 25, 155);
                //                 var email = 'info@recway.nu';
                //                 doc.text("ni skicka ett mejl till ", 25, 160);
                //                 doc.setFont('time', 'bold');
                //                 doc.setFontSize(11);
                //                 doc.text(email, 25 + doc.getTextWidth("ni skicka ett mejl till ") - 3, 160);
                //                 doc.setFontSize(10);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text(" där ni anger order-ID och kort beskriver att kandidaten", 25 + doc.getTextWidth("ni skicka ett mejl till " + email) + 3, 160);
                //                 doc.text("inte visste varför hen blev inbokad.", 25, 165);
                //             }
                //             if (i == 3) {
                //                 doc.setLineWidth(0.1);
                //                 doc.setFontSize(9);
                //                 // first row
                //                 doc.text("Ort och datum:", 27, 58);
                //                 doc.rect(25, 55, 85, 5); // place and date
                //                 doc.text("Intervjuarens namn", 110 + 2, 58); // staff name
                //                 doc.rect(110, 55, 55, 5);
                //                 doc.text("Tid", 165 + 2, 58); // time
                //                 doc.rect(165, 55, 20, 5);
                //                 // second row
                //                 doc.rect(25, 60, 85, 5);
                //                 if (place != null) {
                //                     doc.text(place, 27, 63);
                //                 }
                //                 if (interview_date != null) {
                //                     doc.text(interview_date, 60, 63);
                //                 }
                //                 doc.rect(110, 60, 55, 5);
                //                 if (staff != null) {
                //                     doc.text(staff, 112, 63, {
                //                         'bold': true,
                //                     });
                //                 }
                //                 doc.rect(165, 60, 20, 5);
                //                 if (currentTime != null) {
                //                     doc.text(currentTime, 167, 63);
                //                 }
                //                 // third row
                //                 doc.text("VASCID (om det framgår i portalen)", 27, 68); // VASIC ID if available
                //                 doc.rect(25, 65, 85, 5);
                //                 doc.text("Endast initial på kandidatensnamn", 110 + 2, 68); // Name initial
                //                 doc.rect(110, 65, 55, 5);
                //                 doc.text("OrderID", 165 + 2, 68); // ORDER ID
                //                 doc.rect(165, 65, 20, 5);
                //                 // 4th row
                //                 doc.rect(25, 70, 85, 5);
                //                 if (vasc_id != null) {
                //                     doc.text(vasc_id, 27, 73);
                //                 }
                //                 doc.rect(110, 70, 55, 5);
                //                 if (name != null) {
                //                     doc.text(name, 112, 73);
                //                 }
                //                 doc.rect(165, 70, 20, 5);
                //                 if (order_id != null) {
                //                     doc.text(order_id, 167, 73);
                //                 }
                //                 // 5th row
                //                 doc.text("Beställare/Rekryterande chef", 27, 78);
                //                 doc.setFontSize(7.5);
                //                 doc.text("(ligger under Billing details- Invocie", 27 + doc.getTextWidth("Beställare/Rekryterande chef") + 7, 78);
                //                 doc.text("Recipient)", 27, 81); // invoice recipent 
                //                 doc.rect(25, 75, 85, 8);
                //                 doc.setFontSize(9);
                //                 doc.text("Företag", 110 + 2, 78); // Company
                //                 doc.rect(110, 75, 55, 8);
                //                 doc.rect(165, 75, 20, 13);
                //                 // 6th row
                //                 doc.rect(25, 83, 85, 5);
                //                 if (refperson != null) {
                //                     doc.text(refperson, 27, 86);
                //                 }
                //                 doc.rect(110, 83, 55, 5);
                //                 if (cus_company != null) {
                //                     doc.text(cus_company, 112, 86);
                //                 }

                //                 doc.setFontSize(14);
                //                 doc.text("Intervjuarens samlade bedömning efter utförd intervju", 50, 105);
                //                 doc.setFontSize(9);
                //                 doc.setFont('time', 'bold');

                //                 // 2nd table 1st tr
                //                 doc.text("Bedömning", 25, 110);
                //                 doc.rect(25, 112, 80, 8);
                //                 doc.setFontSize(14);
                //                 doc.text("Pålitlighet", 55, 117);
                //                 doc.setFillColor(37, 163, 0); // green
                //                 doc.rect(105, 112, 17, 8, 'F');
                //                 doc.rect(105, 112, 17, 8);
                //                 doc.rect(112, 112, 4, 4); // checkbox
                //                 doc.setFillColor(255, 217, 0); // yellow
                //                 doc.rect(122, 112, 17, 8, 'F');
                //                 doc.rect(122, 112, 17, 8);
                //                 doc.rect(129, 112, 4, 4); // checkbox
                //                 doc.setFillColor(235, 0, 0); // red
                //                 doc.rect(139, 112, 17, 8, 'F');
                //                 doc.rect(139, 112, 17, 8);
                //                 doc.rect(146, 112, 4, 4); // checkbox
                //                 doc.rect(156, 112, 30, 8);
                //                 doc.setFontSize(8);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text("Kan ej bedömas", 161, 115);
                //                 doc.rect(168, 116, 4, 4); // checkbox
                //                 doc.rect(25, 120, 161, 15);
                //                 doc.text("Anteckningar:", 26, 124);
                //                 // 3rd table
                //                 doc.setFont('time', 'bold');
                //                 doc.setFontSize(14);
                //                 doc.rect(25, 140, 80, 8);
                //                 doc.text("Sårbarhet", 55, 145);
                //                 doc.setFillColor(37, 163, 0); // green
                //                 doc.rect(105, 140, 17, 8, 'F');
                //                 doc.rect(105, 140, 17, 8);
                //                 doc.rect(112, 140, 4, 4); // checkbox
                //                 doc.setFillColor(255, 217, 0); // yellow
                //                 doc.rect(122, 140, 17, 8, 'F');
                //                 doc.rect(122, 140, 17, 8);
                //                 doc.rect(129, 140, 4, 4); // checkbox
                //                 doc.setFillColor(235, 0, 0); // red
                //                 doc.rect(139, 140, 17, 8, 'F');
                //                 doc.rect(139, 140, 17, 8);
                //                 doc.rect(146, 140, 4, 4); // checkbox
                //                 doc.rect(156, 140, 30, 8);
                //                 doc.setFontSize(8);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text("Kan ej bedömas", 161, 143);
                //                 doc.rect(168, 144, 4, 4); // checkbox
                //                 doc.rect(25, 148, 161, 15);
                //                 doc.text("Anteckningar:", 26, 152);
                //                 // 4th table
                //                 doc.setFont('time', 'bold');
                //                 doc.setFontSize(14);
                //                 doc.rect(25, 168, 80, 8);
                //                 doc.text("Lojalitet", 57, 173);
                //                 doc.setFillColor(37, 163, 0); // green
                //                 doc.rect(105, 168, 17, 8, 'F');
                //                 doc.rect(105, 168, 17, 8);
                //                 doc.rect(112, 168, 4, 4); // checkbox
                //                 doc.setFillColor(255, 217, 0); // yellow
                //                 doc.rect(122, 168, 17, 8, 'F');
                //                 doc.rect(122, 168, 17, 8);
                //                 doc.rect(129, 168, 4, 4); // checkbox
                //                 doc.setFillColor(235, 0, 0); // red
                //                 doc.rect(139, 168, 17, 8, 'F');
                //                 doc.rect(139, 168, 17, 8);
                //                 doc.rect(146, 168, 4, 4); // checkbox
                //                 doc.rect(156, 168, 30, 8);
                //                 doc.setFontSize(8);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text("Kan ej bedömas", 161, 171);
                //                 doc.rect(168, 172, 4, 4); // checkbox
                //                 doc.rect(25, 176, 161, 15);
                //                 doc.text("Anteckningar:", 26, 180);
                //                 // 5th table
                //                 doc.setFont('time', 'bold');
                //                 doc.setFontSize(14);
                //                 doc.rect(25, 196, 80, 8);
                //                 doc.text("Säkerhetsmedvetenhet", 40, 201);
                //                 doc.setFillColor(37, 163, 0); // green
                //                 doc.rect(105, 196, 17, 8, 'F');
                //                 doc.rect(105, 196, 17, 8);
                //                 doc.rect(112, 196, 4, 4); // checkbox
                //                 doc.setFillColor(255, 217, 0); // yellow
                //                 doc.rect(122, 196, 17, 8, 'F');
                //                 doc.rect(122, 196, 17, 8);
                //                 doc.rect(129, 196, 4, 4); // checkbox
                //                 doc.setFillColor(235, 0, 0); // red
                //                 doc.rect(139, 196, 17, 8, 'F');
                //                 doc.rect(139, 196, 17, 8);
                //                 doc.rect(146, 196, 4, 4); // checkbox
                //                 doc.rect(156, 196, 30, 8);
                //                 doc.setFontSize(8);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text("Kan ej bedömas", 161, 199);
                //                 doc.rect(168, 200, 4, 4); // checkbox
                //                 doc.rect(25, 204, 161, 15);
                //                 doc.text("Anteckningar:", 26, 208);
                //                 // 6th table
                //                 doc.rect(25, 224, 161, 40);
                //                 doc.setFontSize(10);
                //                 doc.text("Övrigt:", 26, 228);
                //             }
                //             if (i == 4) {
                //                 doc.setFont('helvetica', 'bold');
                //                 doc.text("Summering:", 25, 70);
                //                 doc.rect(25, 75, 161, 70);
                //                 doc.setFontSize(8);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.text("Anteckningar:", 26, 80);
                //                 doc.rect(25, 160, 161, 4);
                //                 doc.text("Identifierad genom", 27, 163);
                //                 doc.rect(25, 164, 161, 5);
                //                 doc.setFontSize(10);
                //                 doc.rect(27, 165.5, 2.5, 2.5);
                //                 doc.text("Körkort", 33, 168);
                //                 doc.rect(55, 165.5, 2.5, 2.5);
                //                 doc.text("ID-kort", 60, 168);
                //                 doc.rect(78, 165.5, 2.5, 2.5);
                //                 doc.text("Pass", 83, 168);
                //                 doc.rect(98, 165.5, 2.5, 2.5);
                //                 doc.text("Annan identitetshandling:", 103, 168);
                //                 doc.rect(25, 169, 161, 4);
                //                 doc.setFontSize(8);
                //                 doc.text("Tjänst/befattning som säkerhetsprövningen avser", 27, 172);
                //                 doc.rect(25, 173, 161, 5);
                //                 doc.setFontSize(14);
                //                 doc.text("Medborgarskap, boendeförhållande och civilstånd", 25, 195);
                //                 doc.setFontSize(8.5);
                //                 doc.rect(25, 198, 80.5, 5);
                //                 doc.text("Medborgarskap (om dubbelt, ange båda)", 27, 202);
                //                 doc.rect(105.5, 198, 80.5, 5);
                //                 doc.text("Ev. tidigare medborgarskap", 107.5, 202);
                //                 doc.rect(25, 203, 80.5, 6);
                //                 doc.rect(105.5, 203, 80.5, 6);
                //                 doc.rect(25, 209, 161, 6);
                //                 doc.text("Födelseort och födelseland", 27, 213);
                //                 doc.rect(25, 215, 161, 6);
                //                 doc.rect(25, 221, 80.5, 5);
                //                 doc.text("Bosatt i Sverige sedan ", 27, 224);
                //                 doc.rect(105.5, 221, 80.5, 5);
                //                 doc.text("Svensk medborgare år", 107.5, 224);
                //                 doc.rect(25, 226, 80.5, 6);
                //                 doc.rect(105.5, 226, 80.5, 6);
                //                 doc.rect(25, 232, 161, 5);
                //                 doc.text("Boendeform (ex. inneboende, hyresrätt, bostadsrätt, villa)", 27, 235);
                //                 doc.rect(25, 237, 161, 6);
                //             }
                //             if (i == 5) {
                //                 // doc.setFont('helvetica', 'bold');
                //                 doc.setFontSize(12);
                //                 doc.text("Livssituation, levnadsbakgrund och umgängeskrets", 25, 70);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.setFontSize(9);
                //                 subParagraphText = "Frågornas syfte är att skapa en tydlig bild av den prövades levnadsbakgrund, livssituation och umgängeskrets.Ställ följdfrågor utifrån den prövades berättelse samt fördjupa dig i områden där den prövade ger generella beskrivningar.Uppmärksamma problem och kriser i den prövades bakgrund och livssituation.Skapa även en bild över den prövades fritid och umgänge.";
                //                 splitSubParagraph = doc.splitTextToSize(subParagraphText, maxWidth);
                //                 splitSubParagraph.forEach((line, index) => {
                //                     var yPosition = 75 + (4 * index);
                //                     doc.text(line, 25, yPosition);
                //                 });
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 94, 161, 5);
                //                 doc.text("Bakgrund samt livssituation (uppväxt, skoltid, relationer, kriser/trauma, sårbarheter mm)", 27, 98);
                //                 doc.rect(25, 99, 161, 80);
                //                 doc.rect(25, 179, 161, 10);
                //                 doc.text("Umgängeskretsen – är den begränsad, omfattande, gamla eller nya vänner, deras yrken och ev. koppling till\nkriminalitet.Finns det några sårbarheter som den prövade har uppmärksammat inom umgängeskretsen ", 27, 183);
                //                 doc.rect(25, 189, 161, 40);
                //                 doc.rect(25, 229, 161, 5);
                //                 doc.text("Fritid – vilka intressen, engagemang, föreningsliv har den prövade och hur ser vardagen ut", 27, 233);
                //                 doc.rect(25, 234, 161, 20);
                //                 doc.rect(25, 254, 161, 5);
                //                 doc.text("Anteckningar", 27, 258);

                //             }
                //             if (i == 6) {
                //                 doc.rect(25, 50, 161, 25);
                //                 doc.setFontSize(12);
                //                 doc.text("Utbildningar och certifikat", 25, 85);
                //                 doc.setFontSize(9);
                //                 doc.rect(25, 87, 161, 10);
                //                 doc.text("Det kan vara av intresse att veta motivet till valet av mer ovanliga utbildningar eller certifikat, i synnerhet om det är\nkunskap som inte har någon koppling till den prövades yrke.", 27, 91);
                //                 doc.rect(25, 97, 161, 5);
                //                 doc.text("Utbildningsbakgrund (gymnasium, högskola/universitet, yrkeshögskola eller liknande samt tidsperiod)", 27, 101);
                //                 doc.rect(25, 102, 161, 25);
                //                 doc.setFontSize(9);
                //                 doc.rect(25, 127, 80.5, 4);
                //                 doc.text("Körkort", 27, 130);
                //                 doc.rect(105.5, 127, 80.5, 4);
                //                 doc.text("Om ja, notera typ", 107.5, 130);

                //                 doc.rect(25, 131, 80.5, 6);
                //                 doc.rect(27, 132, 3, 3);
                //                 doc.text("Nej", 32, 135);
                //                 doc.rect(60, 132, 3, 3);
                //                 doc.text("Ja", 65, 135);
                //                 doc.rect(105.5, 131, 80.5, 6);

                //                 doc.rect(25, 137, 80.5, 4);
                //                 doc.text("Har du haft körkort återkallat eller återlämnat", 27, 140);
                //                 doc.rect(105.5, 137, 80.5, 4);
                //                 doc.text("Om ja, notera när och varför", 107.5, 140);

                //                 doc.rect(25, 141, 80.5, 6);
                //                 doc.rect(27, 142, 3, 3);
                //                 doc.text("Nej", 32, 145);
                //                 doc.rect(60, 142, 3, 3);
                //                 doc.text("Ja", 65, 145);
                //                 doc.rect(105.5, 141, 80.5, 6);

                //                 doc.rect(25, 147, 80.5, 4);
                //                 doc.text("Har du någon licens (inkl. vapenlicens) eller certifikat", 27, 150);
                //                 doc.rect(105.5, 147, 80.5, 4);
                //                 doc.text("Om ja, notera typ", 107.5, 150);

                //                 doc.rect(25, 151, 80.5, 6);
                //                 doc.rect(27, 152, 3, 3);
                //                 doc.text("Nej", 32, 155);
                //                 doc.rect(60, 152, 3, 3);
                //                 doc.text("Ja", 65, 155);
                //                 doc.rect(105.5, 151, 80.5, 6);

                //                 doc.rect(25, 157, 80.5, 7);
                //                 doc.text("Har du genomfört värnplikt/GMU eller annan militär\nutbildning i Sverige eller annat land", 27, 160);
                //                 doc.rect(105.5, 157, 80.5, 7);
                //                 doc.text("Om ja, notera när, var och befattning", 107.5, 160);

                //                 doc.rect(25, 164, 80.5, 6);
                //                 doc.rect(27, 165, 3, 3);
                //                 doc.text("Nej", 32, 168);
                //                 doc.rect(60, 165, 3, 3);
                //                 doc.text("Ja", 65, 168);
                //                 doc.rect(105.5, 164, 80.5, 6);

                //                 doc.rect(25, 170, 161, 5);
                //                 doc.text("Anteckningar", 27, 173);
                //                 doc.rect(25, 175, 161, 30);
                //                 doc.setFontSize(12);
                //                 doc.text("Bolagsengagemang, bisyssla och jäv", 25, 210);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text("Notera den prövades eventuella bolagsengagemang eller bisyssla. Diskutera om detta kan innebära en konflikt\nmed skyddsvärda intressen, eller om det kan orsaka osäkerhet om den prövades lojalitet eller pålitlighet i övrigt\nur säkerhetssynpunkt.Notera även om det förekommit någon jävsituation hos tidigare arbetsgivare ", 25, 215);
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 228, 80.5, 5);
                //                 doc.text("Har du någon bisyssla", 27, 232);
                //                 doc.rect(105.5, 228, 80.5, 5);
                //                 doc.text("Om ja, beskriv bolaget eller bisysslan", 107.5, 232);

                //                 doc.rect(25, 233, 80.5, 6);
                //                 doc.rect(27, 234, 3, 3);
                //                 doc.text("Nej", 32, 237);
                //                 doc.rect(60, 234, 3, 3);
                //                 doc.text("Ja", 65, 237);
                //                 doc.rect(105.5, 233, 80.5, 6);

                //                 doc.rect(25, 239, 80.5, 9);
                //                 doc.text("Har du, nära vän eller släkting bolagsengagemang eller\nbisyssla som kan innebär en intressekonflikt för dig", 27, 243);
                //                 doc.rect(105.5, 239, 80.5, 9);
                //                 doc.text("Om ja, beskriv bolaget eller bisysslan", 107.5, 243);

                //                 doc.rect(25, 248, 80.5, 6);
                //                 doc.rect(27, 249, 3, 3);
                //                 doc.text("Nej", 32, 252);
                //                 doc.rect(60, 249, 3, 3);
                //                 doc.text("Ja", 65, 252);
                //                 doc.rect(105.5, 248, 80.5, 6);

                //                 doc.rect(25, 254, 80.5, 9);
                //                 doc.text("Har du hos tidigare arbetsgivare varit i någon\njävsituation", 27, 258);
                //                 doc.rect(105.5, 254, 80.5, 9);
                //                 doc.text("Om ja, beskriv situationen", 107.5, 258);

                //                 doc.rect(25, 263, 80.5, 6);
                //                 doc.rect(27, 264, 3, 3);
                //                 doc.text("Nej", 32, 267);
                //                 doc.rect(60, 264, 3, 3);
                //                 doc.text("Ja", 65, 267);
                //                 doc.rect(105.5, 263, 80.5, 6);
                //             }
                //             if (i == 7) {
                //                 doc.rect(25, 50, 161, 5);
                //                 doc.rect(25, 55, 161, 15);
                //                 doc.setFontSize(9);
                //                 doc.text("Anteckningar", 27, 54);
                //                 doc.setFontSize(13);
                //                 doc.text("Ekonomi", 25, 77);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text("Skaffa en så klar bild som möjligt av den prövades ekonomiska situation. Hur har den prövade hanterat sin\nekonomi och hur ser den ut idag och i framtiden? Visar det sig att den prövade inte har någon strategi för\nhantering av sin ekonomi, eller om det finns drag av girighet eller orealistisk ekonomisk livsföring, kan denne\nvara sårbar ur säkerhetssynpunkt.Notera om den prövade eller eventuell samboende har flera krediter eller\nskulder utöver bo- och studielån.", 25, 81);
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 100, 161, 5);
                //                 doc.text("Beskriv din ekonomiska situation (lån, krediter, sparande mm)", 27, 103);
                //                 doc.rect(25, 105, 161, 50);

                //                 doc.rect(25, 155, 80.5, 5);
                //                 doc.text("Har dina utgifter någon gång överstigit dina tillgångar", 27, 158);
                //                 doc.rect(105.5, 155, 80.5, 5);
                //                 doc.text("Om ja, varför och i vilken omfattning", 107.5, 158);

                //                 doc.rect(25, 160, 80.5, 6);
                //                 doc.rect(27, 161, 3, 3);
                //                 doc.text("Nej", 32, 164);
                //                 doc.rect(60, 161, 3, 3);
                //                 doc.text("Ja", 65, 164);
                //                 doc.rect(105.5, 160, 80.5, 6);

                //                 doc.rect(25, 166, 80.5, 9);
                //                 doc.text("Har du någon gång lånat pengar för konsumtion (t.ex.\nför resor, kläder, restaurangbesök, utlandsresor)", 27, 169);
                //                 doc.rect(105.5, 166, 80.5, 9);
                //                 doc.text("Om ja, varför och i vilken omfattning", 107.5, 169);

                //                 doc.rect(25, 175, 80.5, 6);
                //                 doc.rect(27, 176, 3, 3);
                //                 doc.text("Nej", 32, 179);
                //                 doc.rect(60, 176, 3, 3);
                //                 doc.text("Ja", 65, 179);
                //                 doc.rect(105.5, 175, 80.5, 6);

                //                 doc.rect(25, 181, 80.5, 9);
                //                 doc.text("Har du lån utan säkerhet (blancolån)?", 27, 184);
                //                 doc.rect(105.5, 181, 80.5, 9);
                //                 doc.text("Om ja, utveckla. När togs lånet, hur stort var lånet och\nvad har pengarna använts till", 107.5, 184);

                //                 doc.rect(25, 190, 80.5, 6);
                //                 doc.rect(27, 191, 3, 3);
                //                 doc.text("Nej", 32, 194);
                //                 doc.rect(60, 191, 3, 3);
                //                 doc.text("Ja", 65, 194);
                //                 doc.rect(105.5, 190, 80.5, 6);

                //                 doc.rect(25, 196, 80.5, 9);
                //                 doc.text("Vid samboende, har ni gemensam ekonomi", 27, 199);
                //                 doc.rect(105.5, 196, 80.5, 9);
                //                 doc.text("Om ja, beskriv hur insatt du är i din samboendes\nekonomi.Har ni gemensamt betalningsansvar?", 107.5, 199);

                //                 doc.rect(25, 205, 80.5, 6);
                //                 doc.rect(27, 206, 3, 3);
                //                 doc.text("Nej", 32, 209);
                //                 doc.rect(60, 206, 3, 3);
                //                 doc.text("Ja", 65, 209);
                //                 doc.rect(105.5, 205, 80.5, 6);

                //                 doc.rect(25, 211, 80.5, 15);
                //                 doc.text("Har eller har du haft någon betalningsanmärkning eller\nliknande registrerade på dig, ditt företag eller någon i\nditt hushåll", 27, 215);
                //                 doc.rect(105.5, 211, 80.5, 15);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning, konsekvenser och hantering", 107.5, 215);

                //                 doc.rect(25, 226, 80.5, 6);
                //                 doc.rect(27, 227, 3, 3);
                //                 doc.text("Nej", 32, 230);
                //                 doc.rect(60, 227, 3, 3);
                //                 doc.text("Ja", 65, 230);
                //                 doc.rect(105.5, 226, 80.5, 6);

                //                 doc.rect(25, 232, 80.5, 9);
                //                 doc.text("Har eller har du haft ett spel- eller köpberoende", 27, 235);
                //                 doc.rect(105.5, 232, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning, konsekvenser och hantering", 107.5, 235);

                //                 doc.rect(25, 241, 80.5, 6);
                //                 doc.rect(27, 242, 3, 3);
                //                 doc.text("Nej", 32, 245);
                //                 doc.rect(60, 242, 3, 3);
                //                 doc.text("Ja", 65, 245);
                //                 doc.rect(105.5, 241, 80.5, 6);

                //                 doc.rect(25, 247, 161, 5);
                //                 doc.text("Anteckningar", 27, 250);
                //                 doc.rect(25, 252, 161, 15);
                //             }
                //             if (i == 8) {
                //                 doc.rect(25, 50, 161, 20);
                //                 doc.setFontSize(13);
                //                 doc.text("Alkohol", 25, 77);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Skapa en uppfattning om den prövades eventuella bruk av alkohol. Om den prövade beskriver\nbeteendeförändringar, negativa humörsvängningar eller minnesluckor i samband med alkoholförtäring eller om\ndenne har blivit avvisad från fester eller offentliga lokaler på grund av alkoholförtäring ska alkoholvanorna\nklargöras. Det kan även vara av intresse att få den prövades bild om hur denne uppfattas av omgivning vid\nonyktert tillstånd: trött, flamsig, pratig (om arbete) eller provocerande/aggressiv/våldsam.`, 25, 81);
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 100, 80.5, 9);
                //                 doc.text("Har eller har du haft ett spel- eller köpberoende", 27, 103);
                //                 doc.rect(105.5, 100, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning, konsekvenser och hantering", 107.5, 103);

                //                 doc.rect(25, 109, 80.5, 6);
                //                 doc.rect(27, 110, 3, 3);
                //                 doc.text("Nej", 32, 113);
                //                 doc.rect(60, 110, 3, 3);
                //                 doc.text("Ja", 65, 113);
                //                 doc.rect(105.5, 109, 80.5, 6);

                //                 doc.rect(25, 115, 161, 5);
                //                 doc.text("Hur reagerar du på en större mängd alkohol", 27, 118);
                //                 doc.rect(25, 120, 161, 20);
                //                 doc.rect(25, 140, 161, 6);
                //                 doc.text("Beskriv i vilka sammanhang du brukar dricka alkoholhaltiga drycker", 27, 143);
                //                 doc.rect(25, 146, 161, 20);
                //                 doc.rect(25, 166, 161, 5);
                //                 doc.text("Beskriv din inställning till alkohol", 27, 169);
                //                 doc.rect(25, 171, 161, 50);

                //                 doc.rect(25, 221, 80.5, 5);
                //                 doc.text("Har du blivit omhändertagen för fylleri", 27, 224);
                //                 doc.rect(105.5, 221, 80.5, 5);
                //                 doc.text("Om ja, beskriv omständigheterna samt när", 107.5, 224);

                //                 doc.rect(25, 226, 80.5, 6);
                //                 doc.rect(27, 227, 3, 3);
                //                 doc.text("Nej", 32, 230);
                //                 doc.rect(60, 227, 3, 3);
                //                 doc.text("Ja", 65, 230);
                //                 doc.rect(105.5, 226, 80.5, 6);

                //                 doc.rect(25, 232, 80.5, 6);
                //                 doc.text("Har eller har du haft alkoholproblem", 27, 235);
                //                 doc.rect(105.5, 232, 80.5, 6);
                //                 doc.text("Om ja, när och varför uppstod problemen", 107.5, 235);

                //                 doc.rect(25, 238, 80.5, 6);
                //                 doc.rect(27, 239, 3, 3);
                //                 doc.text("Nej", 32, 242);
                //                 doc.rect(60, 239, 3, 3);
                //                 doc.text("Ja", 65, 242);
                //                 doc.rect(105.5, 238, 80.5, 6);

                //                 doc.rect(25, 244, 80.5, 9);
                //                 doc.text(`Genomgår eller har du genomgått behandling/\nrehabilitering`, 27, 247);
                //                 doc.rect(105.5, 244, 80.5, 9);
                //                 doc.text(`Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och resultat`, 107.5, 247);

                //                 doc.rect(25, 253, 80.5, 6);
                //                 doc.rect(27, 254, 3, 3);
                //                 doc.text("Nej", 32, 257);
                //                 doc.rect(60, 254, 3, 3);
                //                 doc.text("Ja", 65, 257);
                //                 doc.rect(60, 254, 3, 3);
                //                 doc.rect(105.5, 253, 80.5, 6);

                //                 doc.rect(25, 259, 161, 5);
                //                 doc.text("Intervjuarens anteckningar (Skriv dina anteckningar)", 27, 262);
                //             }
                //             if (i == 9) {
                //                 doc.rect(25, 50, 161, 20);
                //                 doc.setFontSize(13);
                //                 doc.text("Narkotika, narkotikaklassade läkemedel och dopningspreparat", 25, 77);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Här är det viktigt att inte bara konstatera om den prövade missbrukar narkotika utan även dennes inställning\ntill narkotika som njutningsmedel. Narkotikaklassade läkemedel avser här användande av ej egenförskrivna\nläkemedel eller att använda högre doser än vad som är förskrivet.`, 25, 81);
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 94, 80.5, 9);
                //                 doc.text("Har du testat narkotika eller dopningspreparat", 27, 97.5);
                //                 doc.rect(105.5, 94, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och konsekvenser", 107.5, 97.5);

                //                 doc.rect(25, 103, 80.5, 6);
                //                 doc.rect(27, 104, 3, 3);
                //                 doc.text("Nej", 32, 107);
                //                 doc.rect(60, 104, 3, 3);
                //                 doc.text("Ja", 65, 107);
                //                 doc.rect(105.5, 103, 80.5, 6);

                //                 doc.rect(25, 109, 161, 5);
                //                 doc.text("Beskriv din inställning till narkotika och dopningspreparat", 27, 112.5);
                //                 doc.rect(25, 114, 161, 6);

                //                 doc.rect(25, 120, 80.5, 9);
                //                 doc.text(`Har bruket medfört några konsekvenser för ditt arbets-\noch privatliv`, 27, 123.5);
                //                 doc.rect(105.5, 120, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och konsekvenser", 107.5, 123.5);

                //                 doc.rect(25, 129, 80.5, 6);
                //                 doc.rect(27, 130, 3, 3);
                //                 doc.text("Nej", 32, 133);
                //                 doc.rect(60, 130, 3, 3);
                //                 doc.text("Ja", 65, 133);
                //                 doc.rect(105.5, 129, 80.5, 6);

                //                 doc.rect(25, 135, 80.5, 9);
                //                 doc.text("Genomgår eller har du genomgått någon behandling/\nrehabilitering", 27, 138.5);
                //                 doc.rect(105.5, 135, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och resultat", 107.5, 138.5);

                //                 doc.rect(25, 144, 80.5, 6);
                //                 doc.rect(27, 145, 3, 3);
                //                 doc.text("Nej", 32, 148);
                //                 doc.rect(60, 145, 3, 3);
                //                 doc.text("Ja", 65, 148);
                //                 doc.rect(105.5, 144, 80.5, 6);

                //                 doc.rect(25, 150, 161, 5);
                //                 doc.text("Anteckningar", 27, 153.5);
                //                 doc.rect(25, 155, 161, 60);
                //             }
                //             if (i == 10) {
                //                 doc.setFontSize(13);
                //                 doc.text("Brottslig belastning och rättsliga sammanhang", 25, 77);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Ett utfall i polisens register utesluter inte nödvändigtvis en anställning eller annat deltagande i BOLAGENS :s\nverksamhet. Det är dock försvårande om förekomst som inte tagits upp vid säkerhetsprövningssamtalet\npåträffas vid efterföljande registerkontroll. Det är viktigt att den prövade informeras om, och förstår detta.`, 25, 81);
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 94, 80.5, 9);
                //                 doc.text("Har du eller någon i ditt hushåll varit misstänkt för eller\nblivit lagförd för brott i Sverige eller utomlands", 27, 97.5);
                //                 doc.rect(105.5, 94, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och konsekvenser", 107.5, 97.5);

                //                 doc.rect(25, 103, 80.5, 6);
                //                 doc.rect(27, 104, 3, 3);
                //                 doc.text("Nej", 32, 107);
                //                 doc.rect(60, 104, 3, 3);
                //                 doc.text("Ja", 65, 107);
                //                 doc.rect(105.5, 103, 80.5, 6);

                //                 doc.rect(25, 109, 80.5, 9);
                //                 doc.text("Har du eller någon i ditt hushåll varit föremål för annan\nrättslig tvist", 27, 112.5);
                //                 doc.rect(105.5, 109, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och konsekvenser", 107.5, 112.5);

                //                 doc.rect(25, 118, 80.5, 6);
                //                 doc.rect(27, 119, 3, 3);
                //                 doc.text("Nej", 32, 122);
                //                 doc.rect(60, 119, 3, 3);
                //                 doc.text("Ja", 65, 122);
                //                 doc.rect(105.5, 118, 80.5, 6);


                //                 doc.rect(25, 124, 80.5, 9);
                //                 doc.text("Har du varit föremål för annat polisiärt ärende (ex.\nmålsägande, vittne, polisanmälan)", 27, 127.5);
                //                 doc.rect(105.5, 124, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför, dess omfattning och konsekvenser", 107.5, 127.5);

                //                 doc.rect(25, 133, 80.5, 6);
                //                 doc.rect(27, 134, 3, 3);
                //                 doc.text("Nej", 32, 137);
                //                 doc.rect(60, 134, 3, 3);
                //                 doc.text("Ja", 65, 137);
                //                 doc.rect(105.5, 133, 80.5, 6);

                //                 doc.rect(25, 139, 161, 5);
                //                 doc.text("Anteckningar", 27, 142.5);
                //                 doc.rect(25, 144, 161, 90);
                //             }
                //             if (i == 11) {
                //                 doc.setFontSize(13);
                //                 doc.text("Lojalitet och pålitlighet", 25, 77);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Ta reda på om den prövade har släktband, privata, yrkesmässiga eller nationella band till någon organisation,\nnation, eller individ, en tidigare arbetsgivare, som kan påverka dennes lojalitet mot BOLAGENS :s intressen eller\nde intressen som den svenska säkerhetsskyddslagen ska skydda (ex. Sveriges nationella oberoende och\ndemokratiska statsskick). Om sådana band finns, ta reda på om den prövade skulle uppleva det problematiskt\natt skydda sekretessbelagd information från de som han eller hon har lojalitetsband till, eller utnyttja\ninformation han eller hon får tillgång till vid BOLAGENS för egen vinning (ex. vid aktieaffärer). Gör en\nbedömning om personen kan uppfattas som lojal och pålitlig.`, 25, 82, {
                //                     lineHeightFactor: 1.4,
                //                     fontWeight: 'lighter'
                //                 });
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 115, 80.5, 9);
                //                 doc.text("Finns det lojalitets- eller intressekonflikter hos dig\ngentemot företagsverksamhet", 27, 118.5);
                //                 doc.rect(105.5, 115, 80.5, 9);
                //                 doc.text("Om ja, vilka", 107.5, 118.5);

                //                 doc.rect(25, 124, 80.5, 6);
                //                 doc.rect(27, 125, 3, 3);
                //                 doc.text("Nej", 32, 128);
                //                 doc.rect(60, 125, 3, 3);
                //                 doc.text("Ja", 65, 128);
                //                 doc.rect(105.5, 124, 80.5, 6);

                //                 doc.rect(25, 130, 80.5, 15);
                //                 doc.text("Finns det lojalitets- eller intressekonflikter hos dig\ngentemot företag s: verksamhet, Sveriges totalförsvar\neller vårt lands nationella säkerhetsintressen", 27, 133.5);
                //                 doc.rect(105.5, 130, 80.5, 15);
                //                 doc.text("Om ja, på vilket sätt", 107.5, 133.5);

                //                 doc.rect(25, 145, 80.5, 6);
                //                 doc.rect(27, 146, 3, 3);
                //                 doc.text("Nej", 32, 149);
                //                 doc.rect(60, 146, 3, 3);
                //                 doc.text("Ja", 65, 149);
                //                 doc.rect(105.5, 145, 80.5, 6);

                //                 doc.rect(25, 151, 80.5, 15);
                //                 doc.text("Finns det någon situation där du skulle uppleva det\nsom problematiskt att skydda sekretessbelagd eller av\nandra skäl skyddsvärd information", 27, 154.5);
                //                 doc.rect(105.5, 151, 80.5, 15);
                //                 doc.text("Om ja, vilken/vilka situationer då", 107.5, 154.5);

                //                 doc.rect(25, 166, 80.5, 6);
                //                 doc.rect(27, 167, 3, 3);
                //                 doc.text("Nej", 32, 170);
                //                 doc.rect(60, 167, 3, 3);
                //                 doc.text("Ja", 65, 170);
                //                 doc.rect(105.5, 166, 80.5, 6);

                //                 doc.rect(25, 172, 80.5, 9);
                //                 doc.text("Har du vid något tillfälle uppträtt eller handlat illojalt\nmot en tidigare arbetsgivare eller mot Sveriges säkerhet", 26, 175.5);
                //                 doc.rect(105.5, 172, 80.5, 9);
                //                 doc.text("Om ja, på vilket sätt", 107.5, 175.5);

                //                 doc.rect(25, 181, 80.5, 6);
                //                 doc.rect(27, 182, 3, 3);
                //                 doc.text("Nej", 32, 185);
                //                 doc.rect(60, 182, 3, 3);
                //                 doc.text("Ja", 65, 185);
                //                 doc.rect(105.5, 181, 80.5, 6);

                //                 doc.rect(25, 187, 161, 5);
                //                 doc.text("Vad betyder lojalitet för dig", 27, 190);
                //                 doc.rect(25, 192, 161, 35);
                //                 doc.rect(25, 227, 161, 5);
                //                 doc.text("Alla människor har lojalitetsband. Berätta om dina", 27, 230);
                //                 doc.rect(25, 232, 161, 25);
                //             }
                //             if (i == 12) {
                //                 doc.rect(25, 60, 161, 10);
                //                 doc.rect(25, 70, 161, 5);
                //                 doc.setFontSize(9);
                //                 doc.text("Anteckningar", 27, 73);
                //                 doc.rect(25, 75, 161, 10);
                //                 doc.setFontSize(13);
                //                 doc.text("Säkerhetsmedvetande och attityd till säkerhet", 25, 91);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Notera om den prövade genomgått säkerhetsskyddsutbildning. Diskutera även säkerhet i vid bemärkelse samt\nden prövades inställning och förståelse för säkerhet och sekretess.`, 25, 95, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 105, 80.5, 9);
                //                 doc.text("Har du genomgått någon\nsäkerhets(skydds)utbildning", 26, 108.5);
                //                 doc.rect(105.5, 105, 80.5, 9);
                //                 doc.text("Om ja, notera vilken/vilka och när", 107.5, 108.5);

                //                 doc.rect(25, 114, 80.5, 6);
                //                 doc.rect(27, 115, 3, 3);
                //                 doc.text("Nej", 32, 118);
                //                 doc.rect(60, 115, 3, 3);
                //                 doc.text("Ja", 65, 118);
                //                 doc.rect(105.5, 114, 80.5, 6);

                //                 doc.rect(25, 120, 161, 5);
                //                 doc.text("Beskriv din inställning, kunskap och förståelse för säkerhet och sekretess", 27, 123);
                //                 doc.rect(25, 125, 161, 30);
                //                 doc.rect(25, 155, 161, 5);
                //                 doc.text("Notering", 27, 158);
                //                 doc.rect(25, 160, 161, 20);

                //                 doc.setFontSize(13);
                //                 doc.text("Publicitet och exponering på internet", 25, 188);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Diskutera den prövades exponering på internet såsom sociala medier och publicering av fotografier. Diskutera\nvad som dyker upp när den prövade sökt på sig själv. Klargör om den prövade har förståelse för sårbarhet samt\nsekretess och vad som är lämpligt och olämpligt att publicera.`, 25, 192, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 205, 80.5, 5);
                //                 doc.text("Använder du sociala medier", 26, 208.5);
                //                 doc.rect(105.5, 205, 80.5, 5);
                //                 doc.text("Om ja, vilka uppgifter lägger du ut eller delar/gillar", 107.5, 208.5);

                //                 doc.rect(25, 210, 80.5, 6);
                //                 doc.rect(27, 211, 3, 3);
                //                 doc.text("Nej", 32, 214);
                //                 doc.rect(60, 211, 3, 3);
                //                 doc.text("Ja", 65, 214);
                //                 doc.rect(105.5, 210, 80.5, 6);

                //                 doc.rect(25, 216, 80.5, 5);
                //                 doc.text("Framgår det var du arbetar på sociala medier", 26, 219.5);
                //                 doc.rect(105.5, 216, 80.5, 5);
                //                 doc.text("Om ja, på vilka sociala medier", 107.5, 219.5);

                //                 doc.rect(25, 221, 80.5, 6);
                //                 doc.rect(27, 222, 3, 3);
                //                 doc.text("Nej", 32, 225);
                //                 doc.rect(60, 222, 3, 3);
                //                 doc.text("Ja", 65, 225);
                //                 doc.rect(105.5, 221, 80.5, 6);

                //                 doc.rect(25, 227, 80.5, 9);
                //                 doc.text("Är du aktiv i något diskussionsforum eller liknande", 26, 230.5);
                //                 doc.rect(105.5, 227, 80.5, 9);
                //                 doc.text("Om ja, vilka och använder den sökande sitt eget namn\neller ett alias", 107.5, 230.5);

                //                 doc.rect(25, 236, 80.5, 6);
                //                 doc.rect(27, 237, 3, 3);
                //                 doc.text("Nej", 32, 240);
                //                 doc.rect(60, 237, 3, 3);
                //                 doc.text("Ja", 65, 240);
                //                 doc.rect(105.5, 236, 80.5, 6);

                //                 doc.rect(25, 242, 80.5, 9);
                //                 doc.text("Finns det publicerad information om dig som kan\npåverka bilden av din lojalitet eller pålitlighet", 26, 245.5);
                //                 doc.rect(105.5, 242, 80.5, 9);
                //                 doc.text("Om ja, beskriv vad, när och på vilket sätt", 107.5, 245.5);

                //                 doc.rect(25, 251, 80.5, 6);
                //                 doc.rect(27, 252, 3, 3);
                //                 doc.text("Nej", 32, 255);
                //                 doc.rect(60, 252, 3, 3);
                //                 doc.text("Ja", 65, 255);
                //                 doc.rect(105.5, 251, 80.5, 6);

                //                 doc.rect(25, 257, 161, 5);
                //                 doc.text("Anteckningar", 27, 260);
                //                 doc.rect(25, 262, 161, 10);
                //             }
                //             if (i == 13) {
                //                 doc.rect(25, 50, 161, 15);
                //                 doc.setFontSize(13);
                //                 doc.text("Utlandsresor", 25, 78);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Få en bild över den prövades resvanor, både privat och arbete. Vilka länder är intressanta och varför? Vad är\nsyftet med resorna? Kulturellt, historiskt, äventyrligt, sol och bad el. dyl. Skapades nya vänner eller bekanta som\nfortfarande är aktuella? Vad vet den prövade om dessa?`, 25, 83, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 96, 161, 5);
                //                 doc.text("Vilka länder har du besökt de senaste 10 åren och i vilket syfte (både privat och i tjänst)", 27, 99.5);
                //                 doc.rect(25, 101, 161, 30);
                //                 doc.rect(25, 131, 161, 5);
                //                 doc.text("Hur ofta reser du utomlands?", 27, 134.5);
                //                 doc.rect(25, 136, 161, 6);

                //                 doc.rect(25, 142, 80.5, 5);
                //                 doc.text("Kvarvarande kontakter från dessa resor", 26, 145.5);
                //                 doc.rect(105.5, 142, 80.5, 5);
                //                 doc.text("Om ja, information om dessa", 107.5, 145.5);

                //                 doc.rect(25, 147, 80.5, 6);
                //                 doc.rect(27, 148, 3, 3);
                //                 doc.text("Nej", 32, 151);
                //                 doc.rect(60, 148, 3, 3);
                //                 doc.text("Ja", 65, 151);
                //                 doc.rect(105.5, 147, 80.5, 6);

                //                 doc.rect(25, 153, 80.5, 5);
                //                 doc.text("Har du varit skriven/bosatt i utlandet", 26, 156.5);
                //                 doc.rect(105.5, 153, 80.5, 5);
                //                 doc.text("Om ja, när skedde det, var och under hur lång tid", 107.5, 156.5);

                //                 doc.rect(25, 158, 80.5, 6);
                //                 doc.rect(27, 159, 3, 3);
                //                 doc.text("Nej", 32, 162);
                //                 doc.rect(60, 159, 3, 3);
                //                 doc.text("Ja", 65, 162);
                //                 doc.rect(105.5, 158, 80.5, 6);

                //                 doc.rect(25, 164, 161, 5);
                //                 doc.text("Eventuella anhöriga/kontakter som bor utomlands, beskriv relationen och i vilka länder dessa kontakter finns", 27, 167.5);
                //                 doc.rect(25, 169, 161, 35);

                //                 doc.rect(25, 204, 80.5, 5);
                //                 doc.text("Har du tillgångar eller bolagsengagemang utomlands", 26, 207.5);
                //                 doc.rect(105.5, 204, 80.5, 5);
                //                 doc.text("Om ja, utveckla samt ange i vilket eller vilka länder", 107.5, 207.5);

                //                 doc.rect(25, 209, 80.5, 6);
                //                 doc.rect(27, 210, 3, 3);
                //                 doc.text("Nej", 32, 213);
                //                 doc.rect(60, 210, 3, 3);
                //                 doc.text("Ja", 65, 213);
                //                 doc.rect(105.5, 209, 80.5, 6);

                //                 doc.rect(25, 215, 161, 5);
                //                 doc.text("Anteckningar", 27, 218.5);
                //                 doc.rect(25, 220, 161, 45);

                //             }
                //             if (i == 14) {
                //                 doc.setFontSize(13);
                //                 doc.text("Kontakter", 25, 60);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Klargör och exemplifiera att det finns länder, organisationer och individer (både i och utanför Sverige) som har\nintresse av att ta del av uppgifter och materiel som företaget hanterar (exempelvis främmande underrättelse-\neller säkerhetstjänst, försvarsmakter, polis och organiserad brottslighet). Diskutera den prövades syn på detta.`, 25, 66, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 80, 80.5, 15);
                //                 doc.text("Har eller har du haft kontakt med svensk eller utländsk\nunderrättelse- eller säkerhetstjänst, försvarsmakt, polis\neller liknande", 26, 83.5);
                //                 doc.rect(105.5, 80, 80.5, 15);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför och i vilken omfattning", 107.5, 83.5);

                //                 doc.rect(25, 95, 80.5, 6);
                //                 doc.rect(27, 96, 3, 3);
                //                 doc.text("Nej", 32, 99);
                //                 doc.rect(60, 96, 3, 3);
                //                 doc.text("Ja", 65, 99);
                //                 doc.rect(105.5, 95, 80.5, 6);

                //                 doc.rect(25, 101, 80.5, 9);
                //                 doc.text("Har du eller har du haft kontakt med kriminella eller\norganiserad brottslighet", 26, 105.5);
                //                 doc.rect(105.5, 101, 80.5, 9);
                //                 doc.text("Om ja, beskriv omständigheterna, när det skedde,\nvarför och i vilken omfattning", 107.5, 105.5);

                //                 doc.rect(25, 110, 80.5, 6);
                //                 doc.rect(27, 111, 3, 3);
                //                 doc.text("Nej", 32, 114);
                //                 doc.rect(60, 111, 3, 3);
                //                 doc.text("Ja", 65, 114);
                //                 doc.rect(105.5, 110, 80.5, 6);


                //                 doc.rect(25, 116, 161, 5);
                //                 doc.text("Anteckningar", 27, 119.5);
                //                 doc.rect(25, 121, 161, 30);

                //                 doc.setFontSize(13);
                //                 doc.text("Språkkunskaper", 25, 161);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Om den prövade har kunskaper i språk som inte vanligen lärs ut i svenska skolor, fråga varför och var dessa\nkunskaper erhållits. Har den prövade kontakter eller andra relationer till personer i landet där språket talas?`, 25, 166, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');

                //                 doc.rect(25, 179, 161, 5);
                //                 doc.rect(25, 179, 80.5, 25);
                //                 doc.rect(105.5, 179, 80.5, 25);
                //                 doc.text("Modersmål", 27, 182.5);
                //                 doc.text("Vilka andra språk behärskar du och på vilken nivå", 107.5, 182.5);
                //                 doc.rect(25, 204, 161, 5);
                //                 doc.text("Var, när och i vilket syfte har dessa kunskaper erhållits", 27, 207.5);
                //                 doc.rect(25, 209, 161, 10);
                //                 doc.rect(25, 219, 161, 5);
                //                 doc.text("Anteckningar", 27, 222.5);
                //                 doc.rect(25, 224, 161, 15);
                //             }
                //             if (i == 15) {
                //                 doc.setFontSize(13);
                //                 doc.text("Arbetsrelaterade konflikter samt åsidosättande av skyldigheter, ansvar och\narbetsuppgifter", 25, 65, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Notera relevanta arbetsrelaterade konflikter och problem den prövade har stött på och hur dessa har hanterats\nav personen. Av intresse är vad, när, varför samt konsekvenser`, 25, 77, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 83, 161, 9);
                //                 doc.text("Notera relevanta arbetsrelaterade konflikter och problem den prövade har stött på och hur dessa har hanterats av\npersonen. Av intresse är vad, när, varför samt konsekvenser.", 26, 86.5);

                //                 doc.rect(25, 92, 80.5, 9);
                //                 doc.text("Har du i ditt yrkesliv drabbats av några konflikter,\nsäkerhetsrelaterade eller övriga problem", 26, 95.5);
                //                 doc.rect(105.5, 92, 80.5, 9);
                //                 doc.text("Om ja, ange vad och varför och vad konsekvenserna\nblev", 107.5, 95.5);

                //                 doc.rect(25, 101, 80.5, 6);
                //                 doc.rect(27, 102, 3, 3);
                //                 doc.text("Nej", 32, 105);
                //                 doc.rect(60, 102, 3, 3);
                //                 doc.text("Ja", 65, 105);
                //                 doc.rect(105.5, 101, 80.5, 6);

                //                 doc.rect(25, 107, 80.5, 15);
                //                 doc.text("Har du vid en anställning eller liknande brustit i ansvar,\nbegått allvarligt fel som föranlett annan disciplinär\nåtgärd", 26, 111.5);
                //                 doc.rect(105.5, 107, 80.5, 15);
                //                 doc.text("Om ja, vid vilken ansvarsnämnd, påföljd (ex.\nlöneavdrag, varning)", 107.5, 111.5);

                //                 doc.rect(25, 122, 80.5, 6);
                //                 doc.rect(27, 123, 3, 3);
                //                 doc.text("Nej", 32, 125);
                //                 doc.rect(60, 123, 3, 3);
                //                 doc.text("Ja", 65, 125);
                //                 doc.rect(105.5, 122, 80.5, 6);

                //                 doc.rect(25, 128, 80.5, 5);
                //                 doc.text("Har du någon gång erbjudits en muta", 26, 131.5);
                //                 doc.rect(105.5, 128, 80.5, 5);
                //                 doc.text("Om ja, beskriv situationen och hur detta hanterades", 107.5, 131.5);

                //                 doc.rect(25, 133, 80.5, 6);
                //                 doc.rect(27, 134, 3, 3);
                //                 doc.text("Nej", 32, 137);
                //                 doc.rect(60, 134, 3, 3);
                //                 doc.text("Ja", 65, 137);
                //                 doc.rect(105.5, 133, 80.5, 6);

                //                 doc.rect(25, 139, 161, 5);
                //                 doc.text("Anteckningar", 26, 142.5);
                //                 doc.rect(25, 144, 161, 50);
                //             }
                //             if (i == 16) {

                //                 doc.setFontSize(13);
                //                 doc.text("Anställningar och kompletterande information kring CV", 25, 70);
                //                 doc.setFontSize(9);
                //                 doc.setFont('helvetica', 'italic');
                //                 doc.text(`Gå igenom den prövades anställningshistorik och notera anställningarna. Nuvarande anställning ska dock alltid\nnoteras. Tidsluckor i CV och kortare anställningstider ska dock uppmärksammas och orsak undersökas. Bilda dig\näven en uppfattning om att CV stämmer och hur den prövade har fungerat i tidigare anställningar`, 25, 75, {
                //                     lineHeightFactor: 1.4,
                //                 });
                //                 doc.setFont('helvetica', 'normal');
                //                 doc.rect(25, 87, 161, 5);
                //                 doc.text("Nuvarande anställning (arbetsgivare, position, arbetsuppgifter samt tidsperiod)", 26, 90.5);
                //                 doc.rect(25, 92, 161, 30);
                //                 doc.rect(25, 122, 161, 5);
                //                 doc.text("Tidigare anställningar de senaste 10 åren (arbetsgivare, position, arbetsuppgifter samt tidsperiod)", 26, 125.5);
                //                 doc.rect(25, 127, 161, 25);

                //                 doc.rect(25, 152, 80.5, 5);
                //                 doc.text("Finns det någon tidslucka i CV:t som inte redovisats", 26, 155.5);
                //                 doc.rect(105.5, 152, 80.5, 5);
                //                 doc.text("Om ja, vad beror det på", 107.5, 155.5);

                //                 doc.rect(25, 157, 80.5, 6);
                //                 doc.rect(27, 158, 3, 3);
                //                 doc.text("Nej", 32, 161);
                //                 doc.rect(60, 158, 3, 3);
                //                 doc.text("Ja", 65, 161);
                //                 doc.rect(105.5, 157, 80.5, 6);

                //                 doc.rect(25, 163, 80.5, 9);
                //                 doc.text("Finns det information i ditt CV, betyg eller intyg som\nbehöver förtydligas", 26, 166.5);
                //                 doc.rect(105.5, 163, 80.5, 9);
                //                 doc.text("Om ja, vad beror de på", 107.5, 166.5);

                //                 doc.rect(25, 172, 80.5, 6);
                //                 doc.rect(27, 173, 3, 3);
                //                 doc.text("Nej", 32, 176);
                //                 doc.rect(60, 173, 3, 3);
                //                 doc.text("Ja", 65, 176);
                //                 doc.rect(105.5, 172, 80.5, 6);

                //                 doc.rect(25, 178, 161, 5);
                //                 doc.text("Anteckningar", 26, 181.5);
                //                 doc.rect(25, 183, 161, 50);
                //             }
                //             doc.setFont('helvetica', 'normal');
                //             doc.setFontSize(7.5);
                //             doc.setTextColor(128, 128, 128);
                //             doc.text('Recway blankett för säkerhetsprövningsintervju, reviderad ' + formattedDate, 105, 277);
                //             doc.addPage();
                //         }
                //         doc.setFont('helvetica', 'normal');
                //         doc.setFontSize(7.5);
                //         doc.setTextColor(128, 128, 128);
                //         doc.text('Recway blankett för', 38, 16);
                //         doc.text('säkerhetsprövningsintervju, reviderad', 17, 19);
                //         doc.text(formattedDate, 48, 23);
                //         doc.setTextColor(36, 36, 36);
                //         doc.addImage(logoUrl, 'PNG', 13, 25, 48, 16);
                //         var centerX = (doc.internal.pageSize.getWidth() - 160) / 2;
                //         var centerY = (doc.internal.pageSize.getHeight() - 50) / 2;
                //         doc.addImage(logoImage, 'PNG', centerX, centerY, 160, 50);
                //         var textWidth = doc.getStringUnitWidth(`Sida ${i}`) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                //         var rightX = doc.internal.pageSize.getWidth() - textWidth - 24;
                //         doc.text(`Sida`, rightX, 38);
                //         doc.setFontSize(11);
                //         doc.setFont('helvetica', 'bold');
                //         doc.text(`17(17)`, rightX, 43);
                //         doc.setFontSize(10);
                //         doc.setFont('helvetica', 'normal');
                //         doc.setTextColor(0, 0, 0);
                //         doc.setFontSize(13);
                //         doc.text("Bakgrundskontroll", 25, 77);
                //         doc.setFontSize(10);
                //         doc.setFillColor(235, 235, 235); // grey
                //         doc.rect(25, 79, 161, 5, 'F');
                //         doc.rect(25, 89, 161, 20, 'F');

                //         doc.rect(25, 79, 115, 10);
                //         doc.rect(140, 79, 46, 10);
                //         doc.setFont('time', 'normal');
                //         doc.text("Genomförd av Namn / Företag", 26, 82.5);
                //         doc.text("Datum", 142, 82.5);
                //         if (bk_date != null) {
                //             doc.text(bk_date, 142, 87);
                //         }
                //         doc.setFont('time', 'bold');
                //         doc.text("Recway AB ", 26, 87);
                //         doc.setFont('time', 'normal');

                //         doc.rect(25, 89, 161, 35);
                //         doc.text("Lämplig med avseende på", 27, 92.5);

                //         doc.text("Sociala medier:", 29, 99);
                //         doc.text("Ja", 25 + doc.getTextWidth("Sociala medier:") + 10, 99);
                //         doc.rect(25 + doc.getTextWidth("Sociala medier:") + 14, 96, 3, 3);
                //         var checkboxY = 96;
                //         var checkboxSize = 3;
                //         if (social == 1) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(25 + doc.getTextWidth("Sociala medier:") + 14 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }
                //         doc.text("Nej", 25 + doc.getTextWidth("Sociala medier:") + 22, 99);
                //         doc.rect(25 + doc.getTextWidth("Sociala medier:") + 29, 96, 3, 3);
                //         if (social == 0) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(25 + doc.getTextWidth("Sociala medier:") + 29 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }

                //         doc.text("Ekonomi:", 88, 99);
                //         doc.text("Ja", 108, 99);
                //         doc.rect(112, 96, 3, 3);
                //         if (economic == 1) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(112 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }

                //         doc.text("Nej", 118, 99);
                //         doc.rect(124, 96, 3, 3);
                //         if (economic == 0) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(124 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }

                //         doc.text("Personalia:", 135, 99);
                //         doc.text("Ja", 155, 99);
                //         doc.rect(159, 96, 3, 3);
                //         if (criminal == 1) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(159 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }
                //         doc.text("Nej", 167, 99);
                //         doc.rect(173, 96, 3, 3);
                //         if (criminal == 0) {
                //             doc.setFillColor(0, 0, 0);
                //             doc.rect(173 + 0.7, checkboxY + 0.7, checkboxSize - 1.5, checkboxSize - 1.5, 'F');
                //         }
                //         doc.text("Om Nej, notera åtgärd", 27, 108);
                //         doc.setFont('helvetica', 'normal');
                //         doc.setFontSize(7.5);
                //         doc.setTextColor(128, 128, 128);
                //         doc.text('Recway blankett för säkerhetsprövningsintervju, reviderad ' + formattedDate, 105, 277);
                //         // doc.save("a4.pdf");

                //         function convertPDFFileToOtherFormat(pdfFileData) {
                //             var blob = new Blob([pdfFileData], { type: 'application/pdf' });
                //             var inputFile = new File([blob], 'a4.pdf'); // Create a File object from the Blob
                //             convertFile(inputFile);
                //         }
                //         // Function to trigger a conversion process
                //         function convertFile(inputFile) {
                //             var formData = new FormData();
                //             formData.append('file', inputFile); // Assuming inputFile is a File object representing the file to convert
                //             formData.append('output_format', 'docx'); // Specify the desired output format

                //             $.ajax({
                //                 url: 'https://api2.docconversionapi.com/jobs/create',
                //                 type: 'POST',
                //                 data: formData,
                //                 processData: false,
                //                 contentType: false,
                //                 headers: {
                //                     'x-application-id': 'ba08c3c1-879a-452b-9150-70bcf06af40d', // Replace with your actual Application ID
                //                     'x-secret-key': '10586ff6-56a6-4e7b-b5ea-b72996ef8892' // Replace with your actual Secret Key
                //                 },
                //                 success: function (response) {
                //                     // Handle the response (e.g., get the conversionJobId)
                //                     var conversionJobId = response.conversionJobId;
                //                     // Now you can use conversionJobId to check the conversion status
                //                     checkConversionStatus(conversionJobId);
                //                 },
                //                 error: function (xhr, status, error) {
                //                     // Handle errors
                //                     console.error('Error triggering conversion process:', error);
                //                 }
                //             });
                //         }

                //         // Function to check the conversion status
                //         function checkConversionStatus(conversionJobId) {
                //             // Make a GET request to https://api2.docconversionapi.com/jobs/<conversionJobId>
                //             $.ajax({
                //                 url: `https://api2.docconversionapi.com/jobs/${conversionJobId}`,
                //                 type: 'GET',
                //                 headers: {
                //                     'x-application-id': 'ba08c3c1-879a-452b-9150-70bcf06af40d', // Replace with your actual Application ID
                //                     'x-secret-key': '10586ff6-56a6-4e7b-b5ea-b72996ef8892' // Replace with your actual Secret Key
                //                 },
                //                 success: function (response) {
                //                     // Check the status of the conversion job
                //                     if (response.status === 'completed') {
                //                         // Conversion completed, get the converted file or download link
                //                         var convertedFile = response.convertedFile;
                //                         // Handle the converted file (e.g., download it)
                //                         downloadConvertedFile(convertedFile);
                //                     } else if (response.status === 'pending' || response.status === 'processing') {
                //                         // Conversion is still pending or in progress, wait and check again later
                //                         setTimeout(() => {
                //                             checkConversionStatus(conversionJobId);
                //                         }, 5000); // Check again after 5 seconds
                //                     } else {
                //                         // Conversion failed or encountered an error
                //                         console.error('Conversion failed:', response.error);
                //                     }
                //                 },
                //                 error: function (xhr, status, error) {
                //                     // Handle errors
                //                     console.error('Error checking conversion status:', error);
                //                 }
                //             });
                //         }

                //         // Function to download the converted file
                //         function downloadConvertedFile(convertedFile) {
                //             // Handle downloading the converted file (e.g., display a download link)
                //             console.log('Converted file:', convertedFile);
                //         }


                //         var pdfFileData = doc.output('arraybuffer'); 
                //         convertFile(pdfFileData);
                //     }
                // },
                // error: function (e) {
                //     alert("AJAX request failed!");
            }
        }
    });
}