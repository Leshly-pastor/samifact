<template>
    <el-dialog
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :show-close="false"
        :title="titleDialog"
        :visible="showDialog"
        append-to-body
        width="30%"
        @open="create"
    >
        <!--
        <Keypress
            key-event="keyup"
            @success="checkKey"
        />
        -->
        <Keypress
            key-event="keyup"
            :multiple-keys="multiple"
            @success="checkKeyWithAlt"
        />
        <div v-loading="loading">
            <div v-if="form.response_message" class="row mb-4">
                <div class="col-md-12 mt-2">
                    <el-alert
                        :title="form.response_message"
                        :type="form.response_type"
                        show-icon
                    >
                    </el-alert>
                </div>
            </div>
            <div class="row">
                <div
                    v-if="!locked_emission.success"
                    class="col-lg-12 col-md-12 col-sm-12 text-center font-weight-bold"
                >
                    <el-alert
                        :title="locked_emission.message"
                        show-icon
                        type="warning"
                    ></el-alert>
                </div>
            </div>

            <div class="row" v-if="form.send_to_pse">
                <div
                    class="col-lg-12 col-md-12 col-sm-12"
                    v-if="form.response_signature_pse"
                >
                    <el-alert
                        :title="`Firma Xml PSE: ${form.response_signature_pse}`"
                        show-icon
                        type="success"
                    ></el-alert>
                </div>

                <div
                    class="col-lg-12 col-md-12 col-sm-12 mt-3"
                    v-if="form.response_send_cdr_pse"
                >
                    <el-alert
                        :title="`Envio CDR PSE: ${form.response_send_cdr_pse}`"
                        show-icon
                        type="success"
                    ></el-alert>
                </div>
            </div>

            <div class="row justify-content-md-center">
                <div class="col-md-2 text-center font-weight-bold mt-3">
                    <button
                        class="btn btn-info waves-effect waves-light"
                        type="button"
                        @click="clickPrint('a4')"
                    >
                        <i class="fa fa-file-alt"></i>
                    </button>
                    <p>A4</p>
                </div>

                <div
                    v-if="ShowTicket80"
                    class="col-md-2 text-center font-weight-bold mt-3"
                >
                    <button
                        class="btn btn-info waves-effect waves-light"
                        type="button"
                        @click="clickPrint('ticket')"
                    >
                        <i class="fa fa-receipt"></i>
                    </button>
                    <p>80MM</p>
                </div>

                <div
                    v-if="ShowTicket58"
                    class="col-md-2 text-center font-weight-bold mt-3"
                >
                    <button
                        class="btn btn-info waves-effect waves-light"
                        type="button"
                        @click="clickPrint('ticket_58')"
                    >
                        <i class="fa fa-receipt"></i>
                    </button>
                    <p>58MM</p>
                </div>

                <div
                    v-if="ShowTicket50"
                    class="col-md-2 text-center font-weight-bold mt-3"
                >
                    <button
                        class="btn btn-info waves-effect waves-light"
                        type="button"
                        @click="clickPrint('ticket_50')"
                    >
                        <i class="fa fa-receipt"></i>
                    </button>
                    <p>50MM</p>
                </div>

                <div class="col-md-2 text-center font-weight-bold mt-3">
                    <button
                        class="btn btn-info waves-effect waves-light"
                        type="button"
                        @click="clickPrint('a5')"
                    >
                        <i class="fa fa-file-alt"></i>
                    </button>
                    <p>A5</p>
                </div>
            </div>
            <div class="row">
                <div
                    v-if="form.image_detraction"
                    class="col-lg-12 col-md-12 col-sm-12 text-center font-weight-bold mt-3"
                >
                    <a
                        :href="`${this.form.image_detraction}`"
                        class="text-center font-weight-bold text-dark"
                        download
                        >Descargar constancia de pago - detracción</a
                    >
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 mt-2">
                    <label
                        for="emailSendIt"
                        v-if="documentEmailSendIt"
                        class="font-weight-bold text-danger"
                    >
                        Este comprobante ya fue enviado por correo electrónico.
                    </label>
                    <el-input v-model="form.customer_email">
                        <el-button
                            slot="append"
                            :loading="loading"
                            icon="el-icon-message"
                            @click="clickSendEmail"
                            >Enviar
                        </el-button>
                    </el-input>
                    <small
                        v-if="errors.customer_email"
                        class="text-danger"
                        v-text="errors.customer_email[0]"
                    ></small>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 mt-2"  v-if="configuration && !configuration.show_gekawa_mk">
                    <el-input v-model="form.customer_telephone">
                        <template slot="prepend">+51</template>
                        <el-button
                            slot="append"
                            @click="clickSendWhatsapp"
                            :loading="loading_Whatsapp"
                            >Enviar PDF
                            <i class="fab fa-whatsapp"></i>
                        </el-button>
                    </el-input>
                    <small
                        v-if="errors.customer_telephone"
                        class="text-danger"
                        v-text="errors.customer_telephone[0]"
                    ></small>
                </div>

                <div class="col-md-12 mt-2" v-else>
                    <el-input
                        v-model="form.customer_telephone"
                        id="customerTelephone"
                        type="text"
                        placeholder="Número de teléfono"
                        required
                    >
                        <template slot="prepend">+51</template>
                        <el-button
                            slot="append"
                            @click="clickSendWhatsapp3"
                            :disabled="loading_Whatsapp"
                            >Enviar PDF
                            <i class="fab fa-whatsapp"></i>
                        </el-button>
                    </el-input>
                    <small
                        v-if="errors.customer_telephone"
                        class="text-danger"
                        v-text="errors.customer_telephone[0]"
                    ></small>
                </div>

                <!-- <div class="col-md-12 mt-2">
    <div class="input-group">
        
        <div class="input-group-prepend" style="border-radius: 0;">
            <span class="input-group-text">+51</span>
        </div>
        
        
        <input v-model="form.customer_telephone" id="customerTelephone" type="text" class="form-control" placeholder="Número de teléfono" required>
        
        <
        <div class="input-group-append" style="border-radius: 0;">
            <button id="sendWhatsappButton" class="btn btn-primary" @click="clickSendWhatsapp3" :disabled="loading_Whatsapp">
                Enviar PDF por gekawa <i class="fab fa-whatsapp"></i>
            </button>
        </div>
    </div>
    
    <small id="errorCustomerTelephone" class="text-danger"></small>
</div> -->

                <!-- <div class="col-md-12">
    <input id="customerTelephone" type="text" class="form-control" placeholder="Número de teléfono" required>
    <small id="errorCustomerTelephone" class="text-danger"></small>
</div>

<div class="row mt-2">
    <div class="col-md-12">
        <button id="sendWhatsappButton" class="btn btn-primary" @click="clickSendWhatsapp3" :disabled="loading_Whatsapp">
            Enviar PDF por gekawa <i class="fab fa-whatsapp"></i>
        </button>
    </div>
</div> -->

                <div class="col-md-12 mt-2">
                    <el-input
                        v-model="form.customer_telephone"
                        placeholder="Enviar link por WhatsApp"
                    >
                        <template slot="prepend">+51</template>
                        <el-button slot="append" @click="clickSendWhatsapp2"
                            >Enviar URL
                            <el-tooltip
                                class="item"
                                content="Se recomienta tener abierta la sesión de Whatsapp web"
                                effect="dark"
                                placement="top-start"
                            >
                                <i class="fab fa-whatsapp"></i>
                            </el-tooltip>
                        </el-button>
                    </el-input>
                    <small
                        v-if="errors.customer_telephone"
                        class="text-danger"
                        v-text="errors.customer_telephone[0]"
                    ></small>
                </div>
            </div>
            <div
                v-if="company.soap_type_id == '02' && form.group_id == '01'"
                class="row mt-4"
            >
                <div class="col-md-12 text-center">
                    <button
                        class="btn waves-effect waves-light btn-outline-primary"
                        type="button"
                        @click.prevent="clickConsultCdr(form.id)"
                    >
                        Consultar CDR
                    </button>
                </div>
            </div>
        </div>
        <span slot="footer" class="dialog-footer">
            <template v-if="copy == true && isUpdate == false">
                <el-button @click="clickFinalize">Ir al listado</el-button>
                <el-button
                    slot="reference"
                    v-if="!isUpdate"
                    type="primary"
                    @click="clickNewDocuments"
                >
                    Nuevo comprobante
                </el-button>
            </template>
            <template v-else-if="showClose">
                <el-button @click="clickClose">Cerrar</el-button>
            </template>
            <template v-else>
                <el-button class="list" @click="clickFinalize"
                    >Ir al listado</el-button
                >

                <el-popover
                    :open-delay="1000"
                    placement="top-start"
                    width="145"
                    trigger="hover"
                    content="Presiona ALT + N"
                >
                    <el-button
                        slot="reference"
                        v-if="!isUpdate"
                        type="primary"
                        @click="clickNewDocument"
                    >
                        Nuevo comprobante
                    </el-button>
                </el-popover>
            </template>
        </span>
    </el-dialog>
</template>

<script>
import { mapState, mapActions } from "vuex/dist/vuex.mjs";
import Keypress from "vue-keypress";

export default {
    props: [
        "documentEmailSendIt",
        "showDialog",
        "recordId",
        "showClose",
        "isContingency",
        "generatDispatch",
        "dispatchId",
        "isUpdate",
        "configuration",
        "copy",
    ],
    components: {
        Keypress,
    },
    data() {
        return {
            titleDialog: null,
            loading: false,
            loading_Whatsapp: false,
            resource: "documents",
            errors: {},
            form: {},
            multiple: [
                {
                    keyCode: 78, // N
                    modifiers: ["altKey"],
                    preventDefault: true,
                },
                {
                    keyCode: 80, // P
                    modifiers: ["altKey"],
                    preventDefault: true,
                },
            ],
            company: {},
            locked_emission: {},
            // config:{}
        };
    },

    created() {
        this.loadConfiguration(this.$store);
        this.$store.commit("setConfiguration", this.configuration);
    },
    mounted() {
        this.initForm();
    },
    computed: {
        ...mapState(["config"]),
        ShowTicket58: function () {
            if (this.config === undefined) return false;
            if (this.config == null) return false;
            if (this.config.show_ticket_58 === undefined) return false;
            if (this.config.show_ticket_58 == null) return false;
            if (
                this.config.show_ticket_58 !== undefined &&
                this.config.show_ticket_58 !== null
            ) {
                return this.config.show_ticket_58;
            }
            return false;
        },
        ShowTicket80: function () {
            if (this.config === undefined) return false;
            if (this.config == null) return false;
            if (this.config.show_ticket_80 === undefined) return false;
            if (this.config.show_ticket_80 == null) return false;
            if (
                this.config.show_ticket_80 !== undefined &&
                this.config.show_ticket_80 !== null
            ) {
                return this.config.show_ticket_80;
            }
            return false;
        },
        ShowTicket50: function () {
            if (this.config === undefined) return false;
            if (this.config == null) return false;
            if (this.config.show_ticket_50 === undefined) return false;
            if (this.config.show_ticket_50 == null) return false;
            if (
                this.config.show_ticket_50 !== undefined &&
                this.config.show_ticket_50 !== null
            ) {
                return this.config.show_ticket_50;
            }
            return false;
        },
    },
    methods: {
        ...mapActions(["loadConfiguration"]),
        clickSendWhatsapp2() {
            if (!this.form.customer_telephone) {
                return this.$message.error("El número es obligatorio");
            }
            window.open(
                `https://wa.me/51${this.form.customer_telephone}?text=${this.form.message_text}`,
                "_blank"
            );
        },
        clickSendWhatsapp() {
            if (!this.form.customer_telephone) {
                return this.$message.error("El número es obligatorio");
            }
            this.loading_Whatsapp = true;
            let form = {
                id: this.recordId,
                type_id: "FACT",
                customer_telephone: this.form.customer_telephone,
                mensaje:
                    "Su comprobante de pago electrónico " +
                    this.form.number +
                    " ha sido generado correctamente",
            };
            this.$http
                .post(`/whatsapp`, form)
                .then((response) => {
                    console.log("response.data.message", response.data.message);
                    if (response.data.success == true) {
                        this.$message.success(response.data.message);
                        this.loading_Whatsapp = false;
                    } else {
                        this.$message.error(response.data.message);
                        this.loading_Whatsapp = false;
                    }
                })
                .catch((error) => {
                    this.loading_Whatsapp = false;
                    if (error.response.status === 422) {
                        this.$message.error(error.response.data.message);
                    } else {
                        this.$message.error(error.response.data.message);
                    }
                })
                .finally(() => {
                    this.loading_Whatsapp = false;
                });
        },
        getPrintUrl(format) {
            // Obtén la base dinámicamente, por ejemplo, desde el objeto window.location
            const baseUrl = window.location.origin;
            // Luego, forma la URL completa utilizando la base dinámica y la ruta relativa
            return `${baseUrl}/print/document/${this.form.external_id}/${format}`;
        },

        // Reutiliza la función getPrintUrl(format) dentro de clickPrint(format)
        clickPrint(format) {
            const printUrl = this.getPrintUrl(format);
            window.open(printUrl, "_blank");
            return printUrl; // Devuelve la URL para usarla en otro lugar si es necesario
        },
        clickSendWhatsapp3() {
            var customerTelephone =
                document.getElementById("customerTelephone").value;
            var errorsElement = document.getElementById(
                "errorCustomerTelephone"
            );

            // Validación del número de teléfono (por ejemplo, longitud mínima)
            if (customerTelephone.length < 7) {
                errorsElement.textContent =
                    "El número de teléfono debe tener al menos 10 dígitos.";
                return;
            }

            // Datos para enviar a la API de Gekawa
            var formData = new FormData();
            formData.append("appkey", this.company.gekawa_1);
            formData.append("authkey", this.company.gekawa_2);
            formData.append("to", "51" + customerTelephone);

            formData.append("message", "Comprobante de pago electrónico " + this.form.number);

            const printUrl = this.getPrintUrl("a4");

            formData.append("file", printUrl);
            // formData.append('file', 'https://demo.facturaperu.com.pe/print/document/12344e92-47d1-4586-adee-a25359031e96/a4');

            // Realizar la solicitud HTTP
            fetch("https://gekawa.com/api/create-message", {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    // Manejar la respuesta de la API aquí
                    console.log(data);
                })
                .catch((error) => {
                    console.error("Error al enviar la solicitud:", error);
                });
        },

        initForm() {
            this.errors = {};
            this.form = {
                customer_email: null,
                download_pdf: null,
                external_id: null,
                number: null,
                image_detraction: null,
                id: null,
                response_message: null,
                response_type: null,
                customer_telephone: null,
                message_text: null,
                group_id: null,
                send_to_pse: false,
                response_signature_pse: null,
                response_send_cdr_pse: null,
            };
            this.locked_emission = {
                success: true,
                message: null,
            };
            this.company = {
                soap_type_id: null,
                gekawa_1: null,
                gekawa_2: null,
            };
        },
        async create() {
            await this.getCompany();
            await this.getRecord();

            this.loading = true;
            await this.$http
                .get(`/${this.resource}/locked_emission`)
                .then((response) => {
                    this.locked_emission = response.data;
                })
                .finally(() => (this.loading = false));
        },
        async getCompany() {
            this.loading = true;
            await this.$http
                .get(`/companies/record`)
                .then((response) => {
                    if (response.data !== "") {
                        this.company = response.data.data;
                    }
                })
                .finally(() => (this.loading = false));
        },
        async getRecord() {
            this.loading = true;
            await this.$http
                .get(`/${this.resource}/record/${this.recordId}`)
                .then((response) => {
                    this.form = response.data.data;
                    this.titleDialog = "Comprobante: " + this.form.number;
                    if (this.generatDispatch)
                        window.open(
                            `/dispatches/create/${this.form.id}/i/${this.dispatchId}`
                        );
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        clickPrint(format) {
            window.open(
                `/print/document/${this.form.external_id}/${format}`,
                "_blank"
            );
        },
        clickDownloadImage() {
            window.open(`${this.form.image_detraction}`, "_blank");
        },
        clickDownload(format) {
            window.open(`${this.form.download_pdf}/${format}`, "_blank");
        },
        clickSendEmail() {
            this.loading = true;
            this.$http
                .post(`/${this.resource}/email`, {
                    customer_email: this.form.customer_email,
                    id: this.form.id,
                })
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(
                            "El correo fue enviado satisfactoriamente"
                        );
                    } else {
                        this.$message.error("Error al enviar el correo");
                    }
                })
                .catch((error) => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        this.$message.error(error.response.data.message);
                    }
                })
                .then(() => {
                    this.loading = false;
                });
        },
        clickConsultCdr(document_id) {
            this.$http
                .get(`/${this.resource}/consult_cdr/${document_id}`)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.getRecord();
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    this.$message.error(error.response.data.message);
                });
        },
        clickFinalize() {
            location.href = this.isContingency
                ? `/contingencies`
                : `/${this.resource}`;
        },
        clickNewDocument() {
            this.clickClose();
        },
        clickNewDocuments() {
            location.href = `/${this.resource}/create`;
        },
        clickClose() {
            this.$emit("update:showDialog", false);
            this.initForm();
        },
        checkKey(e) {
            let code = e.event.code;
        },
        checkKeyWithAlt(e) {
            let code = e.event.code;
            if (
                // this.showDialogOptions === true &&
                code === "KeyN"
            ) {
                this.clickClose();
            }
            if (code === "KeyP") {
                this.clickPrint("ticket_50"); // Imprime ticket 50 con letra P
            }
        },
    },
};
</script>
