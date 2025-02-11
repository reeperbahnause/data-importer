<!--
  - SubmissionStatus.vue
  - Copyright (c) 2021 james@firefly-iii.org
  -
  - This file is part of the Firefly III Data Importer
  - (https://github.com/firefly-iii/data-importer).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
    <div class="row mt-3">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header">Data submission</div>
                <div class="card-body" v-if="'waiting_to_start' === this.status && false === this.triedToStart">
                    <p>
                        Your converted content will be submitted to your Firefly III installation. Press <strong>Start job</strong> to start.
                    </p>
                    <p>
                        <button class="btn btn-success float-end" v-on:click="callStart" type="button">Start job
                            &rarr;
                        </button>
                    </p>
                </div>
                <div class="card-body" v-if="'waiting_to_start' === this.status && true === this.triedToStart">
                    <p>Waiting for the job to start..</p>
                </div>
                <div class="card-body" v-if="'submission_running' === this.status">
                    <p>
                        Submission is running, please wait.
                    </p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                             aria-valuenow="100" aria-valuemin="0"
                             aria-valuemax="100" style="width: 100%"></div>
                    </div>
                    <submission-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></submission-messages>
                </div>
                <div class="card-body" v-if="'submission_done' === this.status ">
                    <p>
                        The import routine has finished 🎉. Done!
                    </p>
                    <submission-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></submission-messages>
                </div>
                <div class="card-body" v-if="'submission_errored' === this.status">
                    <p class="text-danger">
                        The submission could not be started, or failed due to an error. Please check the log files.
                        Sorry about
                        this :(
                    </p>
                    <submission-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></submission-messages>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SubmissionStatus",
    /*
* The component's data.
*/
    data() {
        return {
            triedToStart: false,
            status: '',
            messages: [],
            warnings: [],
            errors: [],
            downloadUrl: window.configDownloadUrl,
            jobBackUrl: window.jobBackUrl,
            flushUrl: window.flushUrl
        };
    },
    props: [],
    mounted() {
        console.log(`Mounted, check job at ${jobStatusUrl}.`);
        this.getJobStatus();
    },
    methods: {
        getJobStatus: function () {
            console.log('get submission status');
            axios.get(jobStatusUrl).then((response) => {

                // first try post result:
                if (true === this.triedToStart && 'submission_errored' === this.status) {
                    console.error('Job failed! :(');
                    return;
                }

                // handle success
                this.errors = response.data.errors;
                this.warnings = response.data.warnings;
                this.messages = response.data.messages;
                console.log(`Submission status returned is "${response.data.status}".`);
                if (false === this.triedToStart && 'waiting_to_start' === response.data.status) {
                    // call to job start.
                    console.log('Job hasn\'t started yet. Show user some info');
                    this.status = response.data.status;
                    return;
                }
                if (true === this.triedToStart && 'waiting_to_start' === response.data.status) {
                    console.log('Job hasn\'t started yet, but its been tried.');
                }
                if (true === this.triedToStart && 'submission_errored' === response.data.status) {
                    console.error('Job failed');
                    this.status = response.data.status;
                    return;
                }
                if ('submission_running' === response.data.status) {
                    console.log('Job is running...');
                    this.status = response.data.status;
                }
                if ('submission_done' === response.data.status) {
                    console.log('Job is done!');
                    this.status = response.data.status;
                    return;
                }
                if ('submission_errored' === response.data.status) {
                    console.error('Job is kill.');
                    console.error(response.data);
                    return;
                }

                setTimeout(function () {
                    console.log('Fired on setTimeout');
                    this.getJobStatus();
                }.bind(this), 1000);
            });
        },
        callStart: function () {
            console.log('Call job start URL: ' + jobStartUrl);
            axios.post(jobStartUrl).then((response) => {
                console.log('POST was OK');
                this.getJobStatus();
            }).catch((error) => {
                console.error('JOB HAS FAILED :(');
                this.triedToStart = true;
                this.status = 'submission_errored';
            });
            this.getJobStatus();
            this.triedToStart = true;
        },
    },
    watch: {}
}
</script>
