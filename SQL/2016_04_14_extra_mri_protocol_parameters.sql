alter table mri_protocol add column `PulseSequenceName` varchar(255) DEFAULT NULL after Scan_type;
alter table mri_protocol add column `ParallelReductionFactorOutOfPlane` varchar(255) DEFAULT NULL after PulseSequenceName;
alter table mri_protocol add column `ParallelReductionFactorInPlane` varchar(255) DEFAULT NULL after ParallelReductionFactorOutOfPlane;
alter table mri_protocol add column `ParallelAcquisition` varchar(255) DEFAULT NULL after ParallelReductionFactorInPlane;
alter table mri_protocol add column `ParallelAcquisitionTechnique` varchar(255) DEFAULT NULL after ParallelAcquisition;
alter table mri_protocol add column `DiffusionBValue` varchar(255) DEFAULT NULL after ParallelAcquisitionTechnique;
alter table mri_protocol add column `DiffusionGradientOrientation` varchar(255) DEFAULT NULL after DiffusionBValue;
alter table mri_protocol add column `ParallelReductionFactorSecondInPlane` varchar(255) DEFAULT NULL after DiffusionGradientOrientation;
alter table mri_protocol add column `FlowCompensationDirection` varchar(255) DEFAULT NULL after ParallelReductionFactorSecondInPlane;
alter table mri_protocol add column `AcquisitionContrast` varchar(255) DEFAULT NULL after FlowCompensationDirection;
alter table mri_protocol add column `SliceOrientation` varchar(255) DEFAULT NULL after FlowCompensationDirection;
alter table mri_protocol add column `CardiacGating_list` varchar(255) DEFAULT NULL after SliceOrientation;
alter table mri_protocol add column `EPIfactor` varchar(255) DEFAULT NULL after CardiacGating_list;
alter table mri_protocol add column `NumberOfEchoes` varchar(255) DEFAULT NULL after EPIfactor;

